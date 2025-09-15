<?php

namespace App\Services;

use App\Models\Purchase\PurchaseOrder;
use App\Models\Purchase\PurchaseOrderStatusHistory;
use App\Services\ItemTransactionService;
use App\Enums\ItemTransactionUniqueCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;

class PurchaseOrderStatusService
{
    private $itemTransactionService;

    public function __construct(ItemTransactionService $itemTransactionService)
    {
        $this->itemTransactionService = $itemTransactionService;
    }

    /**
     * Update purchase order status with proper inventory handling
     */
    public function updatePurchaseOrderStatus(PurchaseOrder $purchaseOrder, string $newStatus, array $data = []): array
    {
        try {
            DB::beginTransaction();

            $previousStatus = $purchaseOrder->order_status;

            // Validate status transition
            if (!$this->canTransitionToStatus($purchaseOrder, $newStatus)) {
                throw new Exception("Invalid status transition from {$previousStatus} to {$newStatus}");
            }

            // Handle inventory addition/removal based on status
            $inventoryResult = $this->handleInventoryForStatusChange($purchaseOrder, $previousStatus, $newStatus);

            if (!$inventoryResult['success']) {
                throw new Exception($inventoryResult['message']);
            }

            // Handle proof image upload if provided
            $proofImagePath = null;
            if (isset($data['proof_image']) && $data['proof_image']) {
                $proofImagePath = $this->handleProofImageUpload($data['proof_image'], $purchaseOrder->id, $newStatus);
            }

            // Update purchase order status
            $purchaseOrder->update(['order_status' => $newStatus]);

            // Record status change history
            $this->recordStatusHistory($purchaseOrder, $previousStatus, $newStatus, $data['notes'] ?? null, $proofImagePath);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Purchase order status updated successfully',
                'inventory_updated' => $inventoryResult['inventory_updated'] ?? false
            ];

        } catch (Exception $e) {
            DB::rollback();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Validate if status transition is allowed
     */
    private function canTransitionToStatus(PurchaseOrder $purchaseOrder, string $newStatus): bool
    {
        $allowedTransitions = [
            'Pending' => ['Processing', 'Ordered', 'Shipped', 'ROG', 'Cancelled'],
            'Processing' => ['Ordered', 'Shipped', 'ROG', 'Cancelled'],
            'Ordered' => ['Shipped', 'ROG', 'Cancelled'],
            'Shipped' => ['ROG', 'Cancelled', 'Returned'],
            'ROG' => ['Cancelled', 'Returned'], // After receipt of goods
            'Cancelled' => [], // Cannot change from cancelled
            'Returned' => [], // Cannot change from returned
        ];

        return in_array($newStatus, $allowedTransitions[$purchaseOrder->order_status] ?? []);
    }

    /**
     * Handle inventory changes based on status transition
     */
    private function handleInventoryForStatusChange(PurchaseOrder $purchaseOrder, string $previousStatus, string $newStatus): array
    {
        Log::info('Handling inventory for purchase order status change', [
            'purchase_order_id' => $purchaseOrder->id,
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'current_inventory_status' => $purchaseOrder->inventory_status ?? 'pending'
        ]);

        // Status that should trigger inventory addition
        $additionStatuses = ['ROG']; // Receipt of Goods

        // Statuses that should remove inventory (with conditions)
        $removalStatuses = ['Cancelled', 'Returned'];

        $inventoryUpdated = false;

        // If moving TO ROG status - add inventory
        if (in_array($newStatus, $additionStatuses) && ($purchaseOrder->inventory_status ?? 'pending') !== 'added') {
            Log::info('Attempting inventory addition', [
                'purchase_order_id' => $purchaseOrder->id,
                'new_status' => $newStatus,
                'current_inventory_status' => $purchaseOrder->inventory_status ?? 'pending'
            ]);

            $result = $this->addInventory($purchaseOrder);
            if (!$result['success']) {
                Log::error('Inventory addition failed', [
                    'purchase_order_id' => $purchaseOrder->id,
                    'error' => $result['message']
                ]);
                return $result;
            }
            $inventoryUpdated = true;

            Log::info('Inventory addition successful', [
                'purchase_order_id' => $purchaseOrder->id,
                'inventory_updated' => true
            ]);
        }

        // Handle Cancelled/Returned status with ROG consideration
        if (in_array($newStatus, $removalStatuses) && ($purchaseOrder->inventory_status ?? 'pending') === 'added') {
            // Check if the previous status was ROG
            if ($previousStatus === 'ROG') {
                // If coming from ROG, keep inventory added (received items should remain as completed transactions)
                Log::info('Keeping inventory added - purchase was already received (ROG)', [
                    'purchase_order_id' => $purchaseOrder->id,
                    'previous_status' => $previousStatus,
                    'new_status' => $newStatus,
                    'reason' => 'Items were received, treating cancellation/return as separate transaction'
                ]);

                // Update purchase order status to indicate this is a post-receipt action
                $purchaseOrder->update([
                    'inventory_status' => 'added_received',
                    'post_receipt_action' => $newStatus,
                    'post_receipt_action_at' => now()
                ]);
            } else {
                // If NOT coming from ROG, remove inventory (normal cancellation before receipt)
                Log::info('Removing inventory - purchase was not received yet', [
                    'purchase_order_id' => $purchaseOrder->id,
                    'previous_status' => $previousStatus,
                    'new_status' => $newStatus,
                    'reason' => 'Items were not received, safe to remove inventory'
                ]);

                $result = $this->removeInventory($purchaseOrder);
                if (!$result['success']) {
                    return $result;
                }
                $inventoryUpdated = true;
            }
        }

        return [
            'success' => true,
            'inventory_updated' => $inventoryUpdated
        ];
    }

    /**
     * Add inventory for ROG status
     */
    private function addInventory(PurchaseOrder $purchaseOrder): array
    {
        try {
            Log::info('Starting inventory addition for purchase order', [
                'purchase_order_id' => $purchaseOrder->id,
                'current_inventory_status' => $purchaseOrder->inventory_status ?? 'pending',
                'items_count' => $purchaseOrder->itemTransaction->count()
            ]);

            // Check if purchase order has items
            if ($purchaseOrder->itemTransaction->count() == 0) {
                Log::warning('No item transactions found for purchase order', ['purchase_order_id' => $purchaseOrder->id]);
                return ['success' => false, 'message' => 'No items found for inventory addition'];
            }

            // Process inventory addition for each item
            foreach ($purchaseOrder->itemTransaction as $transaction) {
                Log::debug('Processing transaction', [
                    'transaction_id' => $transaction->id,
                    'item_id' => $transaction->item_id,
                    'current_unique_code' => $transaction->unique_code,
                    'quantity' => $transaction->quantity
                ]);

                // Update the transaction unique code from PURCHASE_ORDER to PURCHASE
                $transaction->update([
                    'unique_code' => ItemTransactionUniqueCode::PURCHASE->value
                ]);

                Log::debug('Updated transaction unique_code to PURCHASE', [
                    'transaction_id' => $transaction->id,
                    'new_unique_code' => $transaction->unique_code
                ]);

                // Update inventory quantities
                $this->itemTransactionService->updateItemGeneralQuantityWarehouseWise($transaction->item_id);

                // Handle batch/serial tracking if applicable
                if ($transaction->tracking_type === 'batch') {
                    $this->updateBatchInventoryAfterAddition($transaction);
                } elseif ($transaction->tracking_type === 'serial') {
                    $this->updateSerialInventoryAfterAddition($transaction);
                }
            }

            // Update purchase order inventory status
            $purchaseOrder->update([
                'inventory_status' => 'added',
                'inventory_added_at' => now()
            ]);

            Log::info('Inventory addition completed for purchase order', ['purchase_order_id' => $purchaseOrder->id]);

            return ['success' => true, 'message' => 'Inventory added successfully'];

        } catch (Exception $e) {
            Log::error('Inventory addition failed for purchase order', [
                'purchase_order_id' => $purchaseOrder->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return ['success' => false, 'message' => 'Failed to add inventory: ' . $e->getMessage()];
        }
    }

    /**
     * Remove inventory for Cancelled/Returned status
     */
    private function removeInventory(PurchaseOrder $purchaseOrder): array
    {
        try {
            Log::info('Starting inventory removal for purchase order', [
                'purchase_order_id' => $purchaseOrder->id,
                'items_count' => $purchaseOrder->itemTransaction->count()
            ]);

            // Process inventory removal for each item
            foreach ($purchaseOrder->itemTransaction as $transaction) {
                // Update the transaction unique code from PURCHASE back to PURCHASE_ORDER
                $transaction->update([
                    'unique_code' => ItemTransactionUniqueCode::PURCHASE_ORDER->value
                ]);

                // Update inventory quantities
                $this->itemTransactionService->updateItemGeneralQuantityWarehouseWise($transaction->item_id);

                // Handle batch/serial tracking if applicable
                if ($transaction->tracking_type === 'batch') {
                    $this->updateBatchInventoryAfterRemoval($transaction);
                } elseif ($transaction->tracking_type === 'serial') {
                    $this->updateSerialInventoryAfterRemoval($transaction);
                }
            }

            // Update purchase order inventory status
            $purchaseOrder->update([
                'inventory_status' => 'restored',
                'inventory_added_at' => null
            ]);

            Log::info('Inventory removal completed for purchase order', ['purchase_order_id' => $purchaseOrder->id]);

            return ['success' => true, 'message' => 'Inventory removed successfully'];

        } catch (Exception $e) {
            Log::error('Inventory removal failed for purchase order', [
                'purchase_order_id' => $purchaseOrder->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return ['success' => false, 'message' => 'Failed to remove inventory: ' . $e->getMessage()];
        }
    }

    /**
     * Handle proof image upload
     */
    private function handleProofImageUpload($proofImage, int $purchaseOrderId, string $status): ?string
    {
        try {
            $filename = 'purchase_order_' . $purchaseOrderId . '_' . $status . '_' . time() . '.' . $proofImage->getClientOriginalExtension();
            $path = $proofImage->storeAs('purchase_order_proofs', $filename, 'public');

            Log::info('Proof image uploaded for purchase order', [
                'purchase_order_id' => $purchaseOrderId,
                'status' => $status,
                'path' => $path
            ]);

            return $path;
        } catch (Exception $e) {
            Log::error('Failed to upload proof image for purchase order', [
                'purchase_order_id' => $purchaseOrderId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Record status change history
     */
    private function recordStatusHistory(PurchaseOrder $purchaseOrder, string $previousStatus, string $newStatus, ?string $notes, ?string $proofImagePath): void
    {
        try {
            PurchaseOrderStatusHistory::create([
                'purchase_order_id' => $purchaseOrder->id,
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
                'changed_by' => auth()->id(),
                'notes' => $notes,
                'proof_image_path' => $proofImagePath,
                'changed_at' => now(),
            ]);

            Log::info('Status history recorded for purchase order', [
                'purchase_order_id' => $purchaseOrder->id,
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
                'changed_by' => auth()->id()
            ]);
        } catch (Exception $e) {
            Log::error('Failed to record status history for purchase order', [
                'purchase_order_id' => $purchaseOrder->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get statuses that require proof image
     */
    public function getStatusesRequiringProof(): array
    {
        return ['ROG', 'Cancelled', 'Returned'];
    }

    /**
     * Get status history for a purchase order
     */
    public function getStatusHistory(PurchaseOrder $purchaseOrder): array
    {
        $statusHistory = PurchaseOrderStatusHistory::where('purchase_order_id', $purchaseOrder->id)
            ->with('changedBy')
            ->orderBy('changed_at', 'desc')
            ->get();

        return $statusHistory->map(function ($history) {
            return [
                'id' => $history->id,
                'previous_status' => $history->previous_status,
                'new_status' => $history->new_status,
                'changed_by' => $history->changedBy ? $history->changedBy->username : 'Unknown',
                'changed_by_id' => $history->changed_by,
                'notes' => $history->notes,
                'proof_image_path' => $history->proof_image_path,
                'proof_image_url' => $history->proof_image_path ? Storage::url($history->proof_image_path) : null,
                'changed_at' => $history->changed_at->format('Y-m-d H:i:s'),
                'changed_at_formatted' => $history->changed_at->format('M d, Y \a\t H:i'),
            ];
        })->toArray();
    }

    /**
     * Update batch inventory after addition
     */
    private function updateBatchInventoryAfterAddition($transaction)
    {
        foreach ($transaction->itemBatchTransactions as $batchTransaction) {
            $batchTransaction->update([
                'unique_code' => ItemTransactionUniqueCode::PURCHASE->value
            ]);

            $this->itemTransactionService->updateItemBatchQuantityWarehouseWise(
                $batchTransaction->item_batch_master_id
            );
        }
    }

    /**
     * Update serial inventory after addition
     */
    private function updateSerialInventoryAfterAddition($transaction)
    {
        foreach ($transaction->itemSerialTransaction as $serialTransaction) {
            $serialTransaction->update([
                'unique_code' => ItemTransactionUniqueCode::PURCHASE->value
            ]);

            $this->itemTransactionService->updateItemSerialCurrentStatusWarehouseWise(
                $serialTransaction->item_serial_master_id
            );
        }
    }

    /**
     * Update batch inventory after removal
     */
    private function updateBatchInventoryAfterRemoval($transaction)
    {
        foreach ($transaction->itemBatchTransactions as $batchTransaction) {
            $batchTransaction->update([
                'unique_code' => ItemTransactionUniqueCode::PURCHASE_ORDER->value
            ]);

            $this->itemTransactionService->updateItemBatchQuantityWarehouseWise(
                $batchTransaction->item_batch_master_id
            );
        }
    }

    /**
     * Update serial inventory after removal
     */
    private function updateSerialInventoryAfterRemoval($transaction)
    {
        foreach ($transaction->itemSerialTransaction as $serialTransaction) {
            $serialTransaction->update([
                'unique_code' => ItemTransactionUniqueCode::PURCHASE_ORDER->value
            ]);

            $this->itemTransactionService->updateItemSerialCurrentStatusWarehouseWise(
                $serialTransaction->item_serial_master_id
            );
        }
    }
}
