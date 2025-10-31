<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale\SaleOrder;
use App\Models\Sale\ShipmentTracking;
use App\Services\ShipmentTrackingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ShipmentTrackingController extends Controller
{
    protected $shipmentTrackingService;

    public function __construct(ShipmentTrackingService $shipmentTrackingService)
    {
        $this->shipmentTrackingService = $shipmentTrackingService;
    }

    /**
     * Get a shipment tracking record
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            // Find the tracking
            $tracking = ShipmentTracking::with(['carrier', 'trackingEvents', 'documents'])->find($id);
            if (!$tracking) {
                return response()->json([
                    'status' => false,
                    'message' => 'Shipment tracking not found'
                ], 404);
            }

            return response()->json([
                'status' => true,
                'data' => $tracking
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to get shipment tracking: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new shipment tracking for a sale order
     *
     * @param Request $request
     * @param int $saleOrderId
     * @return JsonResponse
     */
    public function store(Request $request, int $saleOrderId): JsonResponse
    {
        try {
            // Check if sale order exists
            $saleOrder = SaleOrder::find($saleOrderId);
            if (!$saleOrder) {
                return response()->json([
                    'status' => false,
                    'message' => 'Sale order not found'
                ], 404);
            }

            // Prepare data for tracking creation
            $data = $request->only([
                'tracking_number',
                'tracking_url',
                'status',
                'estimated_delivery_date',
                'notes',
                'waybill_number',
                'waybill_type'
            ]);

            // Automatically set carrier_id from sale order if not provided in request
            $data['carrier_id'] = $request->input('carrier_id', $saleOrder->carrier_id);
            $data['sale_order_id'] = $saleOrderId;

            // Create the tracking
            $tracking = $this->shipmentTrackingService->createTracking($data);

            return response()->json([
                'status' => true,
                'message' => 'Shipment tracking created successfully',
                'data' => $tracking
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to create shipment tracking: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an existing shipment tracking
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            // Find the tracking
            $tracking = ShipmentTracking::find($id);
            if (!$tracking) {
                return response()->json([
                    'status' => false,
                    'message' => 'Shipment tracking not found'
                ], 404);
            }

            // Update the tracking
            $data = $request->only([
                'tracking_number',
                'tracking_url',
                'status',
                'estimated_delivery_date',
                'actual_delivery_date',
                'notes',
                'waybill_number',
                'waybill_type'
            ]);

            // Only update carrier_id if explicitly provided in request
            if ($request->has('carrier_id')) {
                $data['carrier_id'] = $request->input('carrier_id');
            }

            $tracking = $this->shipmentTrackingService->updateTracking($tracking, $data);

            return response()->json([
                'status' => true,
                'message' => 'Shipment tracking updated successfully',
                'data' => $tracking
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update shipment tracking: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a shipment tracking
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            // Find the tracking
            $tracking = ShipmentTracking::find($id);
            if (!$tracking) {
                return response()->json([
                    'status' => false,
                    'message' => 'Shipment tracking not found'
                ], 404);
            }

            // Delete the tracking
            $this->shipmentTrackingService->deleteTracking($tracking);

            return response()->json([
                'status' => true,
                'message' => 'Shipment tracking deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete shipment tracking: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add a tracking event to a shipment
     *
     * @param Request $request
     * @param int $trackingId
     * @return JsonResponse
     */
    public function addEvent(Request $request, int $trackingId): JsonResponse
    {
        try {
            // Find the tracking
            $tracking = ShipmentTracking::find($trackingId);
            if (!$tracking) {
                return response()->json([
                    'status' => false,
                    'message' => 'Shipment tracking not found'
                ], 404);
            }

            // Prepare data for event creation
            $data = $request->only([
                'event_date',
                'location',
                'status',
                'description',
                'signature',
                'latitude',
                'longitude'
            ]);

            // Add proof image if provided
            if ($request->hasFile('proof_image')) {
                $data['proof_image'] = $request->file('proof_image');
            }

            // Add the event
            $event = $this->shipmentTrackingService->addTrackingEvent($tracking, $data);

            return response()->json([
                'status' => true,
                'message' => 'Tracking event added successfully',
                'data' => $event
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to add tracking event: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload a document for a shipment tracking
     *
     * @param Request $request
     * @param int $trackingId
     * @return JsonResponse
     */
    public function uploadDocument(Request $request, int $trackingId): JsonResponse
    {
        try {
            // Find the tracking
            $tracking = ShipmentTracking::find($trackingId);
            if (!$tracking) {
                return response()->json([
                    'status' => false,
                    'message' => 'Shipment tracking not found'
                ], 404);
            }

            // Prepare data for document upload
            $data = $request->only([
                'document_type',
                'notes'
            ]);

            $data['file'] = $request->file('file');

            // Upload the document
            $document = $this->shipmentTrackingService->uploadDocument($tracking, $data);

            return response()->json([
                'status' => true,
                'message' => 'Document uploaded successfully',
                'data' => $document
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to upload document: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get tracking history for a sale order
     *
     * @param int $saleOrderId
     * @return JsonResponse
     */
    public function getTrackingHistory(int $saleOrderId): JsonResponse
    {
        try {
            // Check if sale order exists
            $saleOrder = SaleOrder::find($saleOrderId);
            if (!$saleOrder) {
                return response()->json([
                    'status' => false,
                    'message' => 'Sale order not found'
                ], 404);
            }

            // Get tracking history
            $trackingHistory = $this->shipmentTrackingService->getTrackingHistory($saleOrderId);

            return response()->json([
                'status' => true,
                'data' => $trackingHistory
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to get tracking history: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available tracking statuses
     *
     * @return JsonResponse
     */
    public function getStatuses(): JsonResponse
    {
        try {
            $statuses = $this->shipmentTrackingService->getTrackingStatuses();

            return response()->json([
                'status' => true,
                'data' => $statuses
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to get statuses: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available document types
     *
     * @return JsonResponse
     */
    public function getDocumentTypes(): JsonResponse
    {
        try {
            $documentTypes = $this->shipmentTrackingService->getDocumentTypes();

            return response()->json([
                'status' => true,
                'data' => $documentTypes
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to get document types: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a shipment tracking event
     *
     * @param int $eventId
     * @return JsonResponse
     */
    public function deleteEvent(int $eventId): JsonResponse
    {
        try {
            // Find the event
            $event = \App\Models\Sale\ShipmentTrackingEvent::find($eventId);
            if (!$event) {
                return response()->json([
                    'status' => false,
                    'message' => 'Shipment event not found'
                ], 404);
            }

            // Delete the event
            $event->delete();

            return response()->json([
                'status' => true,
                'message' => 'Shipment event deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete shipment event: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate a waybill number format
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function validateWaybill(Request $request): JsonResponse
    {
        try {
            $waybillNumber = $request->input('waybill_number');
            $carrier = $request->input('carrier');

            if (empty($waybillNumber)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Waybill number is required'
                ], 422);
            }

            // Validate the waybill format
            $isValid = $this->shipmentTrackingService->waybillValidationService->validateWaybillFormat($waybillNumber, $carrier);

            return response()->json([
                'status' => true,
                'valid' => $isValid,
                'message' => $isValid ? 'Waybill number is valid' : 'Waybill number format is invalid'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to validate waybill: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate waybill barcode format
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function validateWaybillBarcode(Request $request): JsonResponse
    {
        try {
            $waybillNumber = $request->input('waybill_number');

            if (empty($waybillNumber)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Waybill number is required'
                ], 422);
            }

            // Validate the barcode format
            $isValid = $this->shipmentTrackingService->waybillValidationService->validateWaybillBarcode($waybillNumber);

            return response()->json([
                'status' => true,
                'valid' => $isValid,
                'message' => $isValid ? 'Waybill barcode format is valid' : 'Waybill barcode format is invalid'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to validate waybill barcode: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get waybill validation rules
     *
     * @return JsonResponse
     */
    public function getWaybillRules(): JsonResponse
    {
        try {
            $rules = $this->shipmentTrackingService->waybillValidationService->getWaybillRules();

            return response()->json([
                'status' => true,
                'data' => $rules
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to get waybill rules: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process scanned QR code data
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function processScannedQRCode(Request $request): JsonResponse
    {
        try {
            $waybillNumber = $request->input('waybill_number');

            if (empty($waybillNumber)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Waybill number is required'
                ], 422);
            }

            // Find shipment tracking by waybill number
            $tracking = ShipmentTracking::with(['saleOrder.party', 'carrier'])
                ->where('waybill_number', $waybillNumber)
                ->first();

            if (!$tracking) {
                return response()->json([
                    'status' => false,
                    'message' => 'No shipment tracking found for this waybill number'
                ], 404);
            }

            // Return relevant information
            return response()->json([
                'status' => true,
                'data' => [
                    'tracking' => $tracking,
                    'sale_order' => $tracking->saleOrder,
                    'customer' => $tracking->saleOrder->party ?? null,
                    'carrier' => $tracking->carrier ?? null
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to process scanned QR code: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search for shipment trackingby tracking number for customers
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function searchByTrackingNumber(Request $request): JsonResponse
    {
        try {
            // Validate the request
            $validator = Validator::make($request->all(), [
                'tracking_number' => 'required|string|min:8|max:50',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Support both POST JSON and GET query parameters
            $trackingNumber = $request->input('tracking_number') ?? $request->query('tracking_number');

            // Clean and normalize tracking number
            $cleanTrackingNumber = strtoupper(str_replace([' ', '-', '_'], '', $trackingNumber));

            // Search for shipment tracking by tracking number or waybill number
            $tracking = ShipmentTracking::with([
                'saleOrder' => function($query) {
                    $query->select('id', 'order_code', 'party_id', 'order_status', 'grand_total', 'created_at');
                },
                'saleOrder.party' => function($query) {
                    $query->select('id', 'first_name', 'email', 'phone');
                },
                'carrier' => function($query) {
                    $query->select('id', 'name', 'phone', 'email');
                },
                'trackingEvents' => function($query) {
                    $query->orderBy('event_date', 'desc')->orderBy('created_at', 'desc');
                },
                'documents'
            ])
            ->where(function($query) use ($cleanTrackingNumber, $trackingNumber) {
                $query->where('tracking_number', 'LIKE', '%' . $cleanTrackingNumber . '%')
                      ->orWhere('waybill_number', 'LIKE', '%' . $cleanTrackingNumber . '%')
                      ->orWhere('tracking_number', 'LIKE', '%' . $trackingNumber . '%')
                      ->orWhere('waybill_number', 'LIKE', '%' . $trackingNumber . '%');
            })
            ->first();

            if (!$tracking) {
                return response()->json([
                    'status' => false,
                    'message' => 'No shipment found with this tracking number',
                    'error_code' => 'TRACKING_NOT_FOUND'
                ], 404);
            }

            // Check if user is authenticated and has access to this shipment
            $user = Auth::guard('sanctum')->user();
            if ($user && $tracking->saleOrder && $tracking->saleOrder->party_id !== $user->id) {
                return response()->json([
                    'status' => false,
                    'message' => 'You do not have access to this shipment',
                    'error_code' => 'ACCESS_DENIED'
                ], 403);
            }

            // Format the response data
            $responseData = [
                'tracking_info' => [
                    'id' => $tracking->id,
                    'tracking_number' => $tracking->tracking_number,
                    'waybill_number' => $tracking->waybill_number,
                    'status' => $tracking->status,
                    'estimated_delivery_date' => $tracking->estimated_delivery_date,
                    'actual_delivery_date' => $tracking->actual_delivery_date,
                    'tracking_url' => $tracking->tracking_url,
                    'notes' => $tracking->notes,
                    'created_at' => $tracking->created_at,
                    'updated_at' => $tracking->updated_at,
                ],
                'order_info' => $tracking->saleOrder ? [
                    'id' => $tracking->saleOrder->id,
                    'order_code' => $tracking->saleOrder->order_code,
                    'status' => $tracking->saleOrder->order_status,
                    'total_amount' => $tracking->saleOrder->grand_total,
                    'order_date' => $tracking->saleOrder->created_at,
                ] : null,
                'customer_info' => $tracking->saleOrder && $tracking->saleOrder->party ? [
                    'name' => $tracking->saleOrder->party->first_name,
                    'email' => $tracking->saleOrder->party->email,
                    'phone' => $tracking->saleOrder->party->phone,
                ] : null,
                'carrier_info' => $tracking->carrier ? [
                    'name' => $tracking->carrier->name,
                    'phone' => $tracking->carrier->phone,
                    'email' => $tracking->carrier->email,
                ] : null,
                'tracking_events' => $tracking->trackingEvents->map(function($event) {
                    return [
                        'id' => $event->id,
                        'event_date' => $event->event_date,
                        'location' => $event->location,
                        'status' => $event->status,
                        'description' => $event->description,
                        'signature' => $event->signature,
                        'proof_image_url' => $event->proof_image_url,
                        'latitude' => $event->latitude,
                        'longitude' => $event->longitude,
                        'created_at' => $event->created_at,
                    ];
                }),
                'documents' => $tracking->documents->map(function($document) {
                    return [
                        'id' => $document->id,
                        'document_type' => $document->document_type,
                        'file_name' => $document->file_name,
                        'file_url' => $document->file_url,
                        'notes' => $document->notes,
                        'uploaded_at' => $document->created_at,
                    ];
                }),
                'statistics' => [
                    'total_events' => $tracking->trackingEvents->count(),
                    'latest_event' => $tracking->trackingEvents->first() ? [
                        'date' => $tracking->trackingEvents->first()->event_date,
                        'location' => $tracking->trackingEvents->first()->location,
                        'description' => $tracking->trackingEvents->first()->description,
                    ] : null,
                    'has_documents' => $tracking->documents->count() > 0,
                    'is_delivered' => in_array($tracking->status, ['delivered', 'completed']),
                ]
            ];

            return response()->json([
                'status' => true,
                'message' => 'Shipment tracking found successfully',
                'data' => $responseData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to search shipment tracking: ' . $e->getMessage(),
                'error_code' => 'SEARCH_ERROR'
            ], 500);
        }
    }

    /**
     * Validate tracking number format for customer search
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function validateTrackingNumber(Request $request): JsonResponse
    {
        try {
            // Support both POST JSON and GET query parameters
            $trackingNumber = $request->input('tracking_number') ?? $request->query('tracking_number');

            if (empty($trackingNumber)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Tracking number is required',
                    'valid' => false
                ], 422);
            }

            // Clean the tracking number
            $cleanNumber = strtoupper(str_replace([' ', '-', '_'], '', $trackingNumber));

            // Check minimum length
            if (strlen($cleanNumber) < 8) {
                return response()->json([
                    'status' => true,
                    'valid' => false,
                    'message' => 'Tracking number must be at least 8 characters long'
                ]);
            }

            // Check maximum length
            if (strlen($cleanNumber) > 50) {
                return response()->json([
                    'status' => true,
                    'valid' => false,
                    'message' => 'Tracking number must not exceed 50 characters'
                ]);
            }

            // Common tracking number patterns
            $patterns = [
                '/^[A-Z]{2}\d{9}[A-Z]{2}$/',           // International format (e.g., RR123456789US)
                '/^\d{12,22}$/',                        // Numeric only (12-22 digits)
                '/^[A-Z0-9]{10,30}$/',                  // Alphanumeric (10-30 characters)
                '/^1Z[A-Z0-9]{16}$/',                   // UPS format
                '/^\d{4}\s?\d{4}\s?\d{4}\s?\d{4}$/',   // 16 digits with optional spaces
                '/^[A-Z]{3}\d{8,12}$/',                // Carrier prefix + numbers
                '/^TN\d{8,15}$/',                       // Tracking Number format
            ];

            $isValid = false;
            $matchedPattern = null;

            foreach ($patterns as $index => $pattern) {
                if (preg_match($pattern, $cleanNumber)) {
                    $isValid = true;
                    $matchedPattern = $index + 1;
                    break;
                }
            }

            // Additional validation for specific formats
            if (!$isValid) {
                // Check if it contains at least some numbers and letters
                if (preg_match('/^[A-Z0-9]{8,}$/', $cleanNumber)) {
                    $isValid = true;
                    $matchedPattern = 'generic';
                }
            }

            return response()->json([
                'status' => true,
                'valid' => $isValid,
                'message' => $isValid ? 'Tracking number format is valid' : 'Invalid tracking number format',
                'pattern_matched' => $matchedPattern,
                'cleaned_number' => $cleanNumber
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to validate tracking number: ' . $e->getMessage(),
                'valid' => false
            ], 500);
        }
    }

}
