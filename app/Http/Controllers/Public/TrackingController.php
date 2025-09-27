<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Sale\ShipmentTracking;
use App\Models\Sale\SaleOrder;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TrackingController extends Controller
{
    /**
     * Show the public tracking search page
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('public.tracking.index');
    }

    /**
     * Search for shipment tracking by code
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $trackingCode = $request->input('tracking_code');

            if (empty($trackingCode)) {
                return response()->json([
                    'status' => false,
                    'message' => __('shipment.tracking_code_required')
                ], 422);
            }

            // Rate limiting - allow 10 requests per minute per IP
            $ip = $request->ip();
            $key = "tracking_search_{$ip}";
            $maxAttempts = 10;
            $decayMinutes = 1;

            if (Cache::has($key)) {
                $attempts = Cache::get($key);
                if ($attempts >= $maxAttempts) {
                    return response()->json([
                        'status' => false,
                        'message' => __('shipment.too_many_requests')
                    ], 429);
                }
                Cache::increment($key);
            } else {
                Cache::put($key, 1, now()->addMinutes($decayMinutes));
            }

            // Search for tracking information
            $tracking = $this->findTrackingByCode($trackingCode);

            if (!$tracking) {
                return response()->json([
                    'status' => false,
                    'message' => __('shipment.tracking_not_found')
                ], 404);
            }

            // Prepare response data
            $responseData = $this->prepareTrackingData($tracking);

            return response()->json([
                'status' => true,
                'data' => $responseData
            ]);
        } catch (\Exception $e) {
            Log::error('Tracking search error: ' . $e->getMessage(), [
                'tracking_code' => $trackingCode ?? null,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => false,
                'message' => __('app.something_went_wrong')
            ], 500);
        }
    }

    /**
     * Find tracking by code (waybill, tracking number, or order code)
     *
     * @param string $code
     * @return ShipmentTracking|null
     */
    private function findTrackingByCode(string $code)
    {
        // Try to find by waybill number first
        $tracking = ShipmentTracking::with(['carrier', 'trackingEvents', 'documents', 'saleOrder.party'])
            ->where('waybill_number', $code)
            ->first();

        if ($tracking) {
            return $tracking;
        }

        // Try to find by tracking number
        $tracking = ShipmentTracking::with(['carrier', 'trackingEvents', 'documents', 'saleOrder.party'])
            ->where('tracking_number', $code)
            ->first();

        if ($tracking) {
            return $tracking;
        }

        // Try to find by sale order code
        $saleOrder = SaleOrder::where('order_code', $code)->first();

        if ($saleOrder) {
            $tracking = ShipmentTracking::with(['carrier', 'trackingEvents', 'documents', 'saleOrder.party'])
                ->where('sale_order_id', $saleOrder->id)
                ->first();

            if ($tracking) {
                return $tracking;
            }
        }

        return null;
    }

    /**
     * Prepare tracking data for public display
     *
     * @param ShipmentTracking $tracking
     * @return array
     */
    private function prepareTrackingData(ShipmentTracking $tracking): array
    {
        // Prepare customer information (limited for privacy)
        $customerInfo = null;
        if ($tracking->saleOrder && $tracking->saleOrder->party) {
            $party = $tracking->saleOrder->party;
            $customerInfo = [
                'name' => trim($party->first_name . ' ' . $party->last_name),
                // Only show first name for privacy
                'display_name' => $party->first_name
            ];
        }

        // Prepare tracking events (sorted by date)
        $events = $tracking->trackingEvents->sortBy('event_date')->map(function ($event) {
            return [
                'id' => $event->id,
                'date' => $event->event_date,
                'formatted_date' => $event->event_date->format('M d, Y H:i'),
                'location' => $event->location,
                'status' => $event->status,
                'description' => $event->description,
                'has_proof_image' => !empty($event->proof_image)
            ];
        })->values();

        // Prepare documents
        $documents = $tracking->documents->map(function ($document) {
            return [
                'id' => $document->id,
                'type' => $document->document_type,
                'type_label' => __(sprintf('shipment.%s', strtolower(str_replace(' ', '_', $document->document_type)))),
                'notes' => $document->notes,
                'created_at' => $document->created_at->format('M d, Y')
            ];
        })->values();

        return [
            'shipment' => [
                'id' => $tracking->id,
                'waybill_number' => $tracking->waybill_number,
                'tracking_number' => $tracking->tracking_number,
                'status' => $tracking->status,
                'status_label' => __($tracking->status),
                'estimated_delivery_date' => $tracking->estimated_delivery_date,
                'formatted_estimated_delivery_date' => $tracking->estimated_delivery_date ? $tracking->estimated_delivery_date->format('M d, Y') : null,
                'actual_delivery_date' => $tracking->actual_delivery_date,
                'formatted_actual_delivery_date' => $tracking->actual_delivery_date ? $tracking->actual_delivery_date->format('M d, Y H:i') : null,
                'notes' => $tracking->notes
            ],
            'carrier' => $tracking->carrier ? [
                'id' => $tracking->carrier->id,
                'name' => $tracking->carrier->name
            ] : null,
            'customer' => $customerInfo,
            'sale_order' => $tracking->saleOrder ? [
                'id' => $tracking->saleOrder->id,
                'order_code' => $tracking->saleOrder->order_code,
                'formatted_order_date' => $tracking->saleOrder->formatted_order_date
            ] : null,
            'events' => $events,
            'documents' => $documents,
            'can_print' => !empty($tracking->waybill_number)
        ];
    }

    /**
     * Get document for download
     *
     * @param int $documentId
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function downloadDocument(int $documentId)
    {
        try {
            $document = \App\Models\Sale\ShipmentDocument::find($documentId);

            if (!$document) {
                return response()->json(['message' => 'Document not found'], 404);
            }

            $filePath = storage_path('app/public/' . $document->file_path);

            if (!file_exists($filePath)) {
                return response()->json(['message' => 'File not found'], 404);
            }

            return response()->download($filePath, $document->file_name);
        } catch (\Exception $e) {
            Log::error('Document download error: ' . $e->getMessage());
            return response()->json(['message' => 'Error downloading document'], 500);
        }
    }
}
