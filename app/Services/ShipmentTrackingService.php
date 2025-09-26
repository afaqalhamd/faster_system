<?php

namespace App\Services;

use App\Models\Sale\ShipmentTracking;
use App\Models\Sale\ShipmentTrackingEvent;
use App\Models\Sale\ShipmentDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Exception;

class ShipmentTrackingService
{
    /**
     * Validate tracking data
     *
     * @param array $data
     * @param bool $isUpdate Whether this is an update operation
     * @throws ValidationException
     */
    protected function validateTrackingData(array $data, bool $isUpdate = false): void
    {
        $rules = [
            'sale_order_id' => $isUpdate ? 'sometimes|exists:sale_orders,id' : 'required|exists:sale_orders,id',
            'carrier_id' => 'nullable|exists:carriers,id',
            'tracking_number' => 'nullable|string|max:255',
            'tracking_url' => 'nullable|url|max:500',
            'status' => 'nullable|string|in:Pending,In Transit,Out for Delivery,Delivered,Failed,Returned',
            'estimated_delivery_date' => 'nullable|date',
            'actual_delivery_date' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
        ];

        $validator = validator($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Validate event data
     *
     * @param array $data
     * @throws ValidationException
     */
    protected function validateEventData(array $data): void
    {
        $rules = [
            'shipment_tracking_id' => 'required|exists:shipment_trackings,id',
            'event_date' => 'nullable|date',
            'location' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:Pending,In Transit,Out for Delivery,Delivered,Failed,Returned',
            'description' => 'nullable|string|max:1000',
            'signature' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'proof_image' => 'nullable|file|mimes:jpg,jpeg,png,gif,bmp|max:5120', // 5MB max, image files only
        ];

        $validator = validator($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Validate document data
     *
     * @param array $data
     * @throws ValidationException
     */
    protected function validateDocumentData(array $data): void
    {
        $rules = [
            'shipment_tracking_id' => 'required|exists:shipment_trackings,id',
            'document_type' => 'required|string|in:Invoice,Packing Slip,Delivery Receipt,Proof of Delivery,Customs Document,Other',
            'file' => 'required|file|max:5120', // 5MB max
            'notes' => 'nullable|string|max:1000',
        ];

        $validator = validator($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Create a new shipment tracking record
     *
     * @param array $data
     * @return ShipmentTracking
     * @throws ValidationException
     */
    public function createTracking(array $data): ShipmentTracking
    {
        try {
            // Generate tracking number if not provided
            if (empty($data['tracking_number'])) {
                $data['tracking_number'] = $this->generateTrackingNumber();
            }

            // Validate data
            $this->validateTrackingData($data);

            DB::beginTransaction();

            $tracking = ShipmentTracking::create($data);

            DB::commit();

            return $tracking;
        } catch (ValidationException $e) {
            DB::rollback();
            throw $e;
        } catch (Exception $e) {
            DB::rollback();
            Log::error('Failed to create shipment tracking: ' . $e->getMessage(), [
                'data' => $data,
                'trace' => $e->getTraceAsString()
            ]);
            throw new Exception('Failed to create shipment tracking: ' . $e->getMessage());
        }
    }

    /**
     * Generate a unique tracking number
     *
     * @return string
     */
    private function generateTrackingNumber(): string
    {
        // Generate a tracking number in the format FAT + timestamp + random digits
        $prefix = 'FAT';
        $timestamp = now()->format('ymd'); // Year, month, day
        $random = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT); // 6-digit random number

        return $prefix . $timestamp . $random;
    }

    /**
     * Update an existing shipment tracking record
     *
     * @param ShipmentTracking $tracking
     * @param array $data
     * @return ShipmentTracking
     * @throws ValidationException
     */
    public function updateTracking(ShipmentTracking $tracking, array $data): ShipmentTracking
    {
        try {
            // Validate data for update operation
            $this->validateTrackingData($data, true);

            DB::beginTransaction();

            $tracking->update($data);

            DB::commit();

            return $tracking->refresh();
        } catch (ValidationException $e) {
            DB::rollback();
            throw $e;
        } catch (Exception $e) {
            DB::rollback();
            Log::error('Failed to update shipment tracking: ' . $e->getMessage(), [
                'tracking_id' => $tracking->id,
                'data' => $data,
                'trace' => $e->getTraceAsString()
            ]);
            throw new Exception('Failed to update shipment tracking: ' . $e->getMessage());
        }
    }

    /**
     * Delete a shipment tracking record
     *
     * @param ShipmentTracking $tracking
     * @return bool
     * @throws Exception
     */
    public function deleteTracking(ShipmentTracking $tracking): bool
    {
        try {
            DB::beginTransaction();

            // Delete related events and documents first
            $tracking->trackingEvents()->delete();
            $tracking->documents()->delete();

            // Delete the tracking record
            $result = $tracking->delete();

            DB::commit();

            return $result;
        } catch (Exception $e) {
            DB::rollback();
            Log::error('Failed to delete shipment tracking: ' . $e->getMessage(), [
                'tracking_id' => $tracking->id,
                'trace' => $e->getTraceAsString()
            ]);
            throw new Exception('Failed to delete shipment tracking: ' . $e->getMessage());
        }
    }

    /**
     * Add a tracking event to a shipment
     *
     * @param ShipmentTracking $tracking
     * @param array $data
     * @return ShipmentTrackingEvent
     * @throws ValidationException
     */
    public function addTrackingEvent(ShipmentTracking $tracking, array $data): ShipmentTrackingEvent
    {
        try {
            // Add tracking ID to data
            $data['shipment_tracking_id'] = $tracking->id;

            // Handle proof image upload if provided
            $proofImagePath = null;
            if (isset($data['proof_image']) && $data['proof_image']) {
                $image = $data['proof_image'];
                $directory = "shipment_events/{$tracking->id}";
                $filename = 'proof_' . time() . '.' . $image->getClientOriginalExtension();
                $proofImagePath = $image->storeAs($directory, $filename, 'public');
                $data['proof_image'] = $proofImagePath;
            }

            // Validate data
            $this->validateEventData($data);

            DB::beginTransaction();

            $event = $tracking->trackingEvents()->create($data);

            // Update the tracking status if provided
            if (isset($data['status'])) {
                $tracking->update(['status' => $data['status']]);
            }

            // If this is a delivery event, update the actual delivery date
            if (isset($data['status']) && $data['status'] === 'Delivered') {
                $tracking->update(['actual_delivery_date' => $data['event_date'] ?? now()]);
            }

            DB::commit();

            return $event;
        } catch (ValidationException $e) {
            DB::rollback();
            throw $e;
        } catch (Exception $e) {
            DB::rollback();
            Log::error('Failed to add tracking event: ' . $e->getMessage(), [
                'tracking_id' => $tracking->id,
                'data' => $data,
                'trace' => $e->getTraceAsString()
            ]);
            throw new Exception('Failed to add tracking event: ' . $e->getMessage());
        }
    }

    /**
     * Upload a document for a shipment tracking
     *
     * @param ShipmentTracking $tracking
     * @param array $data
     * @return ShipmentDocument
     * @throws ValidationException
     */
    public function uploadDocument(ShipmentTracking $tracking, array $data): ShipmentDocument
    {
        try {
            // Add tracking ID to data
            $data['shipment_tracking_id'] = $tracking->id;

            // Validate data
            $this->validateDocumentData($data);

            DB::beginTransaction();

            // Handle file upload if provided
            if (isset($data['file']) && $data['file']) {
                $file = $data['file'];
                $directory = "shipment_documents/{$tracking->id}";
                $filename = time() . '_' . $file->getClientOriginalName();

                $path = $file->storeAs($directory, $filename, 'public');

                $data['file_path'] = $path;
                $data['file_name'] = $filename;

                // Remove the file from data as it's not a database field
                unset($data['file']);
            }

            $document = $tracking->documents()->create($data);

            DB::commit();

            return $document;
        } catch (ValidationException $e) {
            DB::rollback();
            throw $e;
        } catch (Exception $e) {
            DB::rollback();
            Log::error('Failed to upload shipment document: ' . $e->getMessage(), [
                'tracking_id' => $tracking->id,
                'data' => $data,
                'trace' => $e->getTraceAsString()
            ]);
            throw new Exception('Failed to upload shipment document: ' . $e->getMessage());
        }
    }

    /**
     * Get tracking history for a sale order
     *
     * @param int $saleOrderId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTrackingHistory(int $saleOrderId)
    {
        return ShipmentTracking::with(['trackingEvents', 'carrier'])
            ->where('sale_order_id', $saleOrderId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get available tracking statuses
     *
     * @return array
     */
    public function getTrackingStatuses(): array
    {
        return [
            'Pending' => 'Pending',
            'In Transit' => 'In Transit',
            'Out for Delivery' => 'Out for Delivery',
            'Delivered' => 'Delivered',
            'Failed' => 'Failed',
            'Returned' => 'Returned'
        ];
    }

    /**
     * Get available document types
     *
     * @return array
     */
    public function getDocumentTypes(): array
    {
        return [
            'Invoice' => 'Invoice',
            'Packing Slip' => 'Packing Slip',
            'Delivery Receipt' => 'Delivery Receipt',
            'Proof of Delivery' => 'Proof of Delivery',
            'Customs Document' => 'Customs Document',
            'Other' => 'Other'
        ];
    }
}
