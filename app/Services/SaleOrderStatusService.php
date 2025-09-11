<?php

namespace App\Services;

use App\Models\Sale\SaleOrder;
use App\Models\Sale\SaleOrderStatusHistory;
use App\Services\ItemTransactionService;
use App\Enums\ItemTransactionUniqueCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;

class SaleOrderStatusService
{
    private $itemTransactionService;

    public function __construct(ItemTransactionService $itemTransactionService)
    {
        $this->itemTransactionService = $itemTransactionService;
    }

    /**
     * Update sale order status with proper inventory handling
     */
    public function updateSaleOrderStatus(SaleOrder $saleOrder, string $newStatus, array $data = []): array
    {
        try {
            DB::beginTransaction();

            $previousStatus = $saleOrder->order_status;

            // Validate status transition
            if (!$this->canTransitionToStatus($saleOrder, $newStatus)) {
                throw new Exception("Invalid status transition from {$previousStatus} to {$newStatus}");
            }

            // Handle inventory deduction/restoration based on status
            $inventoryResult = $this->handleInventoryForStatusChange($saleOrder, $previousStatus, $newStatus);

            if (!$inventoryResult['success']) {
                throw new Exception($inventoryResult['message']);
            }

            // Handle proof image upload if provided
            $proofImagePath = null;
            if (isset($data['proof_image']) && $data['proof_image']) {
                $proofImagePath = $this->handleProofImageUpload($data['proof_image'], $saleOrder->id, $newStatus);
            }

            // Update sale order status
            $saleOrder->update(['order_status' => $newStatus]);

            // Record status change history
            $this->recordStatusHistory($saleOrder, $previousStatus, $newStatus, $data['notes'] ?? null, $proofImagePath);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Sale order status updated successfully',
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
     * Handle inventory changes based on status transition
     */
    private function handleInventoryForStatusChange(SaleOrder $saleOrder, string $previousStatus, string $newStatus): array
    {
        Log::info('Handling inventory for status change', [
            'sale_order_id' => $saleOrder->id,
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'current_inventory_status' => $saleOrder->inventory_status
        ]);

        // Status that should trigger inventory deduction
        $deductionStatuses = ['POD'];

        // Statuses that should restore inventory (with conditions)
        $restorationStatuses = ['Cancelled', 'Returned'];

        $inventoryUpdated = false;

        // If moving TO POD status - deduct inventory
        if (in_array($newStatus, $deductionStatuses) && $saleOrder->inventory_status !== 'deducted') {
            Log::info('Attempting inventory deduction', [
                'sale_order_id' => $saleOrder->id,
                'new_status' => $newStatus,
                'current_inventory_status' => $saleOrder->inventory_status
            ]);

            $result = $this->deductInventory($saleOrder);
            if (!$result['success']) {
                Log::error('Inventory deduction failed', [
                    'sale_order_id' => $saleOrder->id,
                    'error' => $result['message']
                ]);
                return $result;
            }
            $inventoryUpdated = true;

            Log::info('Inventory deduction successful', [
                'sale_order_id' => $saleOrder->id,
                'inventory_updated' => true
            ]);
        } else {
            Log::info('Skipping inventory deduction', [
                'sale_order_id' => $saleOrder->id,
                'reason' => $saleOrder->inventory_status === 'deducted' ? 'Already deducted' : 'Not POD status',
                'new_status' => $newStatus,
                'is_pod_status' => in_array($newStatus, $deductionStatuses),
                'current_inventory_status' => $saleOrder->inventory_status
            ]);
        }

        // NEW LOGIC: Handle Cancelled/Returned status with POD consideration
        if (in_array($newStatus, $restorationStatuses) && $saleOrder->inventory_status === 'deducted') {
            // Check if the previous status was POD
            if ($previousStatus === 'POD') {
                // If coming from POD, keep inventory deducted (delivered items should remain as completed transactions)
                Log::info('Keeping inventory deducted - sale order was already delivered (POD)', [
                    'sale_order_id' => $saleOrder->id,
                    'previous_status' => $previousStatus,
                    'new_status' => $newStatus,
                    'reason' => 'Items were delivered, treating cancellation/return as separate transaction'
                ]);

                // Update sale order status to indicate this is a post-delivery cancellation/return
                $saleOrder->update([
                    'inventory_status' => 'deducted_delivered', // New status to indicate delivered but cancelled/returned
                    'post_delivery_action' => $newStatus, // Track what action was taken after delivery
                    'post_delivery_action_at' => now()
                ]);

                // Note: Any return/cancellation of delivered items should be handled as a separate
                // return transaction or credit note, not by restoring the original sale order inventory
            } else {
                // If NOT coming from POD, restore inventory (normal cancellation before delivery)
                Log::info('Restoring inventory - sale order was not delivered yet', [
                    'sale_order_id' => $saleOrder->id,
                    'previous_status' => $previousStatus,
                    'new_status' => $newStatus,
                    'reason' => 'Items were not delivered, safe to restore inventory'
                ]);

                $result = $this->restoreInventory($saleOrder);
                if (!$result['success']) {
                    return $result;
                }
                $inventoryUpdated = true;
            }
        }

        // If moving FROM POD to other status (except Cancelled/Returned) - keep inventory deducted
        if ($previousStatus === 'POD' && !in_array($newStatus, array_merge($deductionStatuses, $restorationStatuses))) {
            // Keep inventory deducted, no action needed
            Log::info('Keeping inventory deducted when moving from POD', [
                'sale_order_id' => $saleOrder->id,
                'previous_status' => $previousStatus,
                'new_status' => $newStatus
            ]);
        }

        return [
            'success' => true,
            'inventory_updated' => $inventoryUpdated
        ];
    }

    /**
     * Deduct inventory for POD status
     */
    private function deductInventory(SaleOrder $saleOrder): array
    {
        try {
            Log::info('Starting inventory deduction for sale order', [
                'sale_order_id' => $saleOrder->id,
                'current_inventory_status' => $saleOrder->inventory_status,
                'items_count' => $saleOrder->itemTransaction->count()
            ]);

            // Check if sale order has items
            if ($saleOrder->itemTransaction->count() == 0) {
                Log::warning('No item transactions found for sale order', ['sale_order_id' => $saleOrder->id]);
                return ['success' => false, 'message' => 'No items found for inventory deduction'];
            }

            // Process inventory deduction for each item
            foreach ($saleOrder->itemTransaction as $transaction) {
                Log::debug('Processing transaction', [
                    'transaction_id' => $transaction->id,
                    'item_id' => $transaction->item_id,
                    'current_unique_code' => $transaction->unique_code,
                    'quantity' => $transaction->quantity
                ]);

                // Update the transaction unique code from SALE_ORDER to SALE
                $transaction->update([
                    'unique_code' => ItemTransactionUniqueCode::SALE->value
                ]);

                Log::debug('Updated transaction unique_code to SALE', [
                    'transaction_id' => $transaction->id,
                    'new_unique_code' => $transaction->unique_code
                ]);

                // Update inventory quantities
                $this->itemTransactionService->updateItemGeneralQuantityWarehouseWise($transaction->item_id);

                // Handle batch/serial tracking if applicable
                if ($transaction->tracking_type === 'batch') {
                    $this->updateBatchInventoryAfterDeduction($transaction);
                } elseif ($transaction->tracking_type === 'serial') {
                    $this->updateSerialInventoryAfterDeduction($transaction);
                }
            }

            // Update sale order inventory status
            $saleOrder->update([
                'inventory_status' => 'deducted',
                'inventory_deducted_at' => now()
            ]);

            Log::info('Inventory deduction completed for sale order', [
                'sale_order_id' => $saleOrder->id,
                'new_inventory_status' => 'deducted',
                'deducted_at' => now()
            ]);

            return ['success' => true, 'message' => 'Inventory deducted successfully'];

        } catch (Exception $e) {
            Log::error('Inventory deduction failed for sale order', [
                'sale_order_id' => $saleOrder->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return ['success' => false, 'message' => 'Failed to deduct inventory: ' . $e->getMessage()];
        }
    }

    /**
     * Restore inventory for Cancelled/Returned status
     */
    private function restoreInventory(SaleOrder $saleOrder): array
    {
        try {
            Log::info('Starting inventory restoration for sale order', [
                'sale_order_id' => $saleOrder->id,
                'items_count' => $saleOrder->itemTransaction->count()
            ]);

            // Process inventory restoration for each item
            foreach ($saleOrder->itemTransaction as $transaction) {
                // Update the transaction unique code from SALE back to SALE_ORDER (or remove it)
                $transaction->update([
                    'unique_code' => ItemTransactionUniqueCode::SALE_ORDER->value
                ]);

                // Update inventory quantities
                $this->itemTransactionService->updateItemGeneralQuantityWarehouseWise($transaction->item_id);

                // Handle batch/serial tracking if applicable
                if ($transaction->tracking_type === 'batch') {
                    $this->updateBatchInventoryAfterRestoration($transaction);
                } elseif ($transaction->tracking_type === 'serial') {
                    $this->updateSerialInventoryAfterRestoration($transaction);
                }
            }

            // Update sale order inventory status
            $saleOrder->update([
                'inventory_status' => 'restored',
                'inventory_deducted_at' => null
            ]);

            Log::info('Inventory restoration completed for sale order', ['sale_order_id' => $saleOrder->id]);

            return ['success' => true, 'message' => 'Inventory restored successfully'];

        } catch (Exception $e) {
            Log::error('Inventory restoration failed for sale order', [
                'sale_order_id' => $saleOrder->id,
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'message' => 'Failed to restore inventory: ' . $e->getMessage()];
        }
    }

    /**
     * Update batch inventory after deduction
     */
    private function updateBatchInventoryAfterDeduction($transaction)
    {
        foreach ($transaction->itemBatchTransactions as $batchTransaction) {
            $batchTransaction->update([
                'unique_code' => ItemTransactionUniqueCode::SALE->value
            ]);

            $this->itemTransactionService->updateItemBatchQuantityWarehouseWise(
                $batchTransaction->item_batch_master_id
            );
        }
    }

    /**
     * Update serial inventory after deduction
     */
    private function updateSerialInventoryAfterDeduction($transaction)
    {
        foreach ($transaction->itemSerialTransaction as $serialTransaction) {
            $serialTransaction->update([
                'unique_code' => ItemTransactionUniqueCode::SALE->value
            ]);

            $this->itemTransactionService->updateItemSerialCurrentStatusWarehouseWise(
                $serialTransaction->item_serial_master_id
            );
        }
    }

    /**
     * Update batch inventory after restoration
     */
    private function updateBatchInventoryAfterRestoration($transaction)
    {
        foreach ($transaction->itemBatchTransactions as $batchTransaction) {
            $batchTransaction->update([
                'unique_code' => ItemTransactionUniqueCode::SALE_ORDER->value
            ]);

            $this->itemTransactionService->updateItemBatchQuantityWarehouseWise(
                $batchTransaction->item_batch_master_id
            );
        }
    }

    /**
     * Update serial inventory after restoration
     */
    private function updateSerialInventoryAfterRestoration($transaction)
    {
        foreach ($transaction->itemSerialTransaction as $serialTransaction) {
            $serialTransaction->update([
                'unique_code' => ItemTransactionUniqueCode::SALE_ORDER->value
            ]);

            $this->itemTransactionService->updateItemSerialCurrentStatusWarehouseWise(
                $serialTransaction->item_serial_master_id
            );
        }
    }

    /**
     * Check if status transition is allowed
     */
    private function canTransitionToStatus(SaleOrder $saleOrder, string $newStatus): bool
    {
        $currentStatus = $saleOrder->order_status;

        // Define allowed transitions
        $allowedTransitions = [
            'Pending' => ['Processing', 'Completed', 'Delivery', 'POD', 'Cancelled'],
            'Processing' => ['Completed', 'Delivery', 'POD', 'Cancelled'],
            'Completed' => ['Delivery', 'POD', 'Cancelled', 'Returned'],
            'Delivery' => ['POD', 'Cancelled', 'Returned'],
            'POD' => ['Completed', 'Delivery', 'Cancelled', 'Returned'],
            'Cancelled' => [], // Cannot change from cancelled
            'Returned' => [], // Cannot change from returned
        ];

        return in_array($newStatus, $allowedTransitions[$currentStatus] ?? []);
    }

    /**
     * Handle proof image upload
     */
    private function handleProofImageUpload($image, int $saleOrderId, string $status): string
    {
        $directory = "sale_orders/status_proofs/{$saleOrderId}";
        $filename = $status . '_' . time() . '.' . $image->getClientOriginalExtension();

        return $image->storeAs($directory, $filename, 'public');
    }

    /**
     * Record status change in history
     */
    private function recordStatusHistory(SaleOrder $saleOrder, ?string $previousStatus, string $newStatus, ?string $notes, ?string $proofImage): void
    {
        // Record status history in the dedicated table
        SaleOrderStatusHistory::create([
            'sale_order_id' => $saleOrder->id,
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'notes' => $notes,
            'proof_image' => $proofImage,
            'changed_by' => auth()->id() ?? 1, // Fallback to user ID 1 if no auth
            'changed_at' => now(),
        ]);
    }

    /**
     * Get status history for a sale order
     */
    public function getStatusHistory(SaleOrder $saleOrder): array
    {
        $histories = $saleOrder->saleOrderStatusHistories()
            ->with(['changedBy:id,first_name,last_name,email'])
            ->orderBy('changed_at', 'desc')
            ->get();

        // Transform the data to ensure user information is included
        return $histories->map(function ($history) {
            $data = $history->toArray();

            // Add user information if available
            if ($history->changedBy) {
                $data['changed_by_name'] = trim($history->changedBy->first_name . ' ' . $history->changedBy->last_name);
            } else {
                $data['changed_by_name'] = 'Unknown';
            }

            return $data;
        })->toArray();
    }

    /**
     * Get statuses that require proof image and notes
     */
    public function getStatusesRequiringProof(): array
    {
        return ['POD', 'Cancelled', 'Returned'];
    }
}
