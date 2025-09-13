<?php

namespace App\Services;

use App\Models\Purchase\Purchase;
use App\Models\PurchaseStatusHistory;
use App\Services\ItemTransactionService;
use App\Enums\ItemTransactionUniqueCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;

class PurchaseStatusService
{
    private $itemTransactionService;

    public function __construct(ItemTransactionService $itemTransactionService)
    {
        $this->itemTransactionService = $itemTransactionService;
    }

    /**
     * Update purchase status with proper inventory handling
     */
    public function updatePurchaseStatus(Purchase $purchase, string $newStatus, array $data = []): array
    {
        try {
            DB::beginTransaction();

            $previousStatus = $purchase->purchase_status;

            // Validate status transition
            if (!$this->canTransitionToStatus($purchase, $newStatus)) {
                throw new Exception("Invalid status transition from {$previousStatus} to {$newStatus}");
            }

            // Handle inventory addition/restoration based on status
            $inventoryResult = $this->handleInventoryForStatusChange($purchase, $previousStatus, $newStatus);

            if (!$inventoryResult['success']) {
                throw new Exception($inventoryResult['message']);
            }

            // Handle proof image upload if provided
            $proofImagePath = null;
            if (isset($data['proof_image']) && $data['proof_image']) {
                $proofImagePath = $this->handleProofImageUpload($data['proof_image'], $purchase->id, $newStatus);
            }

            // Update purchase status
            $purchase->update(['purchase_status' => $newStatus]);

            // Record status change history
            $this->recordStatusHistory($purchase, $previousStatus, $newStatus, $data['notes'] ?? null, $proofImagePath);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Purchase status updated successfully',
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
    private function handleInventoryForStatusChange(Purchase $purchase, string $previousStatus, string $newStatus): array
    {
        Log::info('Handling inventory for purchase status change', [
            'purchase_id' => $purchase->id,
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'current_inventory_status' => $purchase->inventory_status
        ]);

        // Status that should trigger inventory addition (equivalent to POD for purchases - Receipt of Goods)
        $additionStatuses = ['ROG']; // ROG = Receipt of Goods

        // Statuses that should remove inventory (with conditions)
        $removalStatuses = ['Cancelled', 'Returned'];

        $inventoryUpdated = false;

        // If moving TO ROG status - add inventory
        if (in_array($newStatus, $additionStatuses) && $purchase->inventory_status !== 'added') {
            Log::info('Attempting inventory addition', [
                'purchase_id' => $purchase->id,
                'new_status' => $newStatus,
                'current_inventory_status' => $purchase->inventory_status
            ]);

            $result = $this->addInventory($purchase);
            if (!$result['success']) {
                Log::error('Inventory addition failed', [
                    'purchase_id' => $purchase->id,
                    'error' => $result['message']
                ]);
                return $result;
            }
            $inventoryUpdated = true;

            Log::info('Inventory addition successful', [
                'purchase_id' => $purchase->id,
                'inventory_updated' => true
            ]);
        } else {
            Log::info('Skipping inventory addition', [
                'purchase_id' => $purchase->id,
                'reason' => $purchase->inventory_status === 'added' ? 'Already added' : 'Not ROG status',
                'new_status' => $newStatus,
                'is_rog_status' => in_array($newStatus, $additionStatuses),
                'current_inventory_status' => $purchase->inventory_status
            ]);
        }

        // Handle Cancelled/Returned status with ROG consideration
        if (in_array($newStatus, $removalStatuses)) {
            // Check if inventory has been added (from ROG status)
            if ($purchase->inventory_status === 'added') {
                if ($newStatus === 'Returned') {
                    // For returns after ROG: Keep inventory, mark as post-receipt return
                    // Returns should be handled as separate return transactions
                    Log::info('Keeping inventory for post-receipt return', [
                        'purchase_id' => $purchase->id,
                        'previous_status' => $previousStatus,
                        'new_status' => $newStatus,
                        'reason' => 'Returns after receipt should be handled as separate transactions'
                    ]);

                    $purchase->update([
                        'inventory_status' => 'added_received', // Keep inventory but mark as received
                        'post_receipt_action' => $newStatus,
                        'post_receipt_action_at' => now()
                    ]);
                } elseif ($newStatus === 'Cancelled') {
                    // For cancellations after ROG: Remove inventory
                    Log::info('Removing inventory for post-receipt cancellation', [
                        'purchase_id' => $purchase->id,
                        'previous_status' => $previousStatus,
                        'new_status' => $newStatus,
                        'reason' => 'Cancellation after receipt requires inventory removal'
                    ]);

                    $result = $this->removeInventory($purchase);
                    if (!$result['success']) {
                        return $result;
                    }
                    $inventoryUpdated = true;

                    $purchase->update([
                        'post_receipt_action' => $newStatus,
                        'post_receipt_action_at' => now()
                    ]);
                }
            } else {
                // If inventory was not added yet (before ROG), remove it for both cancel and return
                Log::info('Removing inventory for pre-receipt cancellation/return', [
                    'purchase_id' => $purchase->id,
                    'previous_status' => $previousStatus,
                    'new_status' => $newStatus,
                    'reason' => 'Items were not received yet, safe to cancel/return'
                ]);

                // For pre-receipt cancellation/return, just mark the status
                // No inventory removal needed as it wasn't added yet
            }
        }

        // If moving FROM ROG to other status (except Cancelled/Returned) - keep inventory added
        if ($previousStatus === 'ROG' && !in_array($newStatus, array_merge($additionStatuses, $removalStatuses))) {
            // Keep inventory added, no action needed
            Log::info('Keeping inventory added when moving from ROG', [
                'purchase_id' => $purchase->id,
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
     * Add inventory for ROG status
     */
    private function addInventory(Purchase $purchase): array
    {
        try {
            Log::info('Starting inventory addition for purchase', [
                'purchase_id' => $purchase->id,
                'current_inventory_status' => $purchase->inventory_status,
                'items_count' => $purchase->itemTransaction->count()
            ]);

            // Check if purchase has items
            if ($purchase->itemTransaction->count() == 0) {
                Log::warning('No item transactions found for purchase', ['purchase_id' => $purchase->id]);
                return ['success' => false, 'message' => 'No items found for inventory addition'];
            }

            // Process inventory addition for each item
            foreach ($purchase->itemTransaction as $transaction) {
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

                Log::info('Inventory quantity updated after ROG status change', [
                    'transaction_id' => $transaction->id,
                    'item_id' => $transaction->item_id,
                    'warehouse_id' => $transaction->warehouse_id,
                    'quantity' => $transaction->quantity,
                    'unique_code_after_update' => $transaction->refresh()->unique_code
                ]);

                // Handle batch/serial tracking if applicable
                if ($transaction->tracking_type === 'batch') {
                    $this->updateBatchInventoryAfterAddition($transaction);
                } elseif ($transaction->tracking_type === 'serial') {
                    $this->updateSerialInventoryAfterAddition($transaction);
                }
            }

            // Update purchase inventory status
            $purchase->update([
                'inventory_status' => 'added',
                'inventory_added_at' => now()
            ]);

            Log::info('Inventory addition completed for purchase', [
                'purchase_id' => $purchase->id,
                'new_inventory_status' => 'added',
                'added_at' => now()
            ]);

            return ['success' => true, 'message' => 'Inventory added successfully'];

        } catch (Exception $e) {
            Log::error('Inventory addition failed for purchase', [
                'purchase_id' => $purchase->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return ['success' => false, 'message' => 'Failed to add inventory: ' . $e->getMessage()];
        }
    }

    /**
     * Remove inventory for Cancelled/Returned status
     */
    private function removeInventory(Purchase $purchase): array
    {
        try {
            Log::info('Starting inventory removal for purchase', [
                'purchase_id' => $purchase->id,
                'items_count' => $purchase->itemTransaction->count()
            ]);

            // Process inventory removal for each item
            foreach ($purchase->itemTransaction as $transaction) {
                // Update the transaction unique code from PURCHASE back to PURCHASE_ORDER (or remove it)
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

            // Update purchase inventory status
            $purchase->update([
                'inventory_status' => 'removed',
                'inventory_added_at' => null
            ]);

            Log::info('Inventory removal completed for purchase', ['purchase_id' => $purchase->id]);

            return ['success' => true, 'message' => 'Inventory removed successfully'];

        } catch (Exception $e) {
            Log::error('Inventory removal failed for purchase', [
                'purchase_id' => $purchase->id,
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'message' => 'Failed to remove inventory: ' . $e->getMessage()];
        }
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

    /**
     * Check if status transition is allowed
     */
    private function canTransitionToStatus(Purchase $purchase, string $newStatus): bool
    {
        $currentStatus = $purchase->purchase_status;

        // Define allowed transitions for purchases
        $allowedTransitions = [
            'Pending' => ['Processing', 'Ordered', 'Shipped', 'ROG', 'Cancelled'],
            'Processing' => ['Ordered', 'Shipped', 'ROG', 'Cancelled'],
            'Ordered' => ['Shipped', 'ROG', 'Cancelled'],
            'Shipped' => ['ROG', 'Cancelled', 'Returned'],
            'ROG' => ['Cancelled', 'Returned'], // Once ROG is reached, no backward transitions allowed
            'Cancelled' => [], // Cannot change from cancelled
            'Returned' => [], // Cannot change from returned
        ];

        return in_array($newStatus, $allowedTransitions[$currentStatus] ?? []);
    }

    /**
     * Handle proof image upload
     */
    private function handleProofImageUpload($image, int $purchaseId, string $status): string
    {
        $directory = "purchases/status_proofs/{$purchaseId}";
        $filename = $status . '_' . time() . '.' . $image->getClientOriginalExtension();

        return $image->storeAs($directory, $filename, 'public');
    }

    /**
     * Record status change in history
     */
    private function recordStatusHistory(Purchase $purchase, ?string $previousStatus, string $newStatus, ?string $notes, ?string $proofImage): void
    {
        $changedBy = auth()->id();

        // Fallback to a system user if no authenticated user (for console commands, etc.)
        if (!$changedBy) {
            // Try to find the first admin user or system user
            $systemUser = \App\Models\User::where('email', 'like', '%admin%')
                ->orWhere('first_name', 'like', '%system%')
                ->orWhere('first_name', 'like', '%admin%')
                ->orWhere('last_name', 'like', '%system%')
                ->orWhere('last_name', 'like', '%admin%')
                ->first();

            if ($systemUser) {
                $changedBy = $systemUser->id;
            } else {
                // If no admin found, use the first user in the system
                $firstUser = \App\Models\User::first();
                if ($firstUser) {
                    $changedBy = $firstUser->id;
                } else {
                    // Log warning and skip creating history if no users exist
                    Log::warning('Status change recorded without any users in system', [
                        'purchase_id' => $purchase->id,
                        'new_status' => $newStatus
                    ]);
                    return;
                }
            }

            Log::info('Status change recorded with fallback user', [
                'purchase_id' => $purchase->id,
                'new_status' => $newStatus,
                'fallback_user_id' => $changedBy
            ]);
        }

        PurchaseStatusHistory::create([
            'purchase_id' => $purchase->id,
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'notes' => $notes,
            'proof_image' => $proofImage,
            'changed_by' => $changedBy,
            'changed_at' => now(),
        ]);
    }

    /**
     * Get status history for a purchase
     */
    public function getStatusHistory(Purchase $purchase): array
    {
        $histories = $purchase->purchaseStatusHistories()
            ->with(['changedBy:id,first_name,last_name,email'])
            ->orderBy('changed_at', 'desc')
            ->get();

        // Transform the data to ensure user information is included
        return $histories->map(function ($history) {
            $data = $history->toArray();

            // Ensure user data is properly included
            if ($history->changedBy) {
                $data['changed_by'] = [
                    'id' => $history->changedBy->id,
                    'name' => trim($history->changedBy->first_name . ' ' . $history->changedBy->last_name),
                    'email' => $history->changedBy->email
                ];
            } else {
                // Try to load the user manually if relationship failed
                $user = \App\Models\User::find($history->changed_by);
                if ($user) {
                    $data['changed_by'] = [
                        'id' => $user->id,
                        'name' => trim($user->first_name . ' ' . $user->last_name),
                        'email' => $user->email
                    ];
                } else {
                    $data['changed_by'] = [
                        'id' => $history->changed_by,
                        'name' => 'User Not Found (ID: ' . $history->changed_by . ')',
                        'email' => null
                    ];
                }
            }

            return $data;
        })->toArray();
    }

    /**
     * Get statuses that require proof image and notes
     */
    public function getStatusesRequiringProof(): array
    {
        return ['ROG', 'Cancelled', 'Returned'];
    }
}
