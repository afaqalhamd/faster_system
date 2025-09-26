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
                'notes'
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
                'notes'
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
}
