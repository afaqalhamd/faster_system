<?php

namespace App\Services;

use App\Models\Sale\Sale;
use App\Models\SalesStatusHistory;
use App\Services\ItemTransactionService;
use App\Enums\ItemTransactionUniqueCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;

class SalesStatusService
{
    private $itemTransactionService;

    public function __construct(ItemTransactionService $itemTransactionService)
    {
        $this->itemTransactionService = $itemTransactionService;
    }

    /**
     * Update sales status with proper inventory handling
     */
    public function updateSalesStatus(Sale $sale, string $newStatus, array $data = []): array
    {
        try {
            DB::beginTransaction();

            $previousStatus = $sale->sales_status;

            // Validate status transition
            if (!$this->canTransitionToStatus($sale, $newStatus)) {
                throw new Exception("Invalid status transition from {$previousStatus} to {$newStatus}");
            }

            // Handle inventory deduction/restoration based on status
            $inventoryResult = $this->handleInventoryForStatusChange($sale, $previousStatus, $newStatus);

            if (!$inventoryResult['success']) {
                throw new Exception($inventoryResult['message']);
            }

            // Handle proof image upload if provided
            $proofImagePath = null;
            if (isset($data['proof_image']) && $data['proof_image']) {
                $proofImagePath = $this->handleProofImageUpload($data['proof_image'], $sale->id, $newStatus);
            }

            // Update sale status
            $sale->update(['sales_status' => $newStatus]);

            // Record status change history
            $this->recordStatusHistory($sale, $previousStatus, $newStatus, $data['notes'] ?? null, $proofImagePath);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Sales status updated successfully',
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
    private function handleInventoryForStatusChange(Sale $sale, string $previousStatus, string $newStatus): array
    {
        Log::info('Handling inventory for status change', [
            'sale_id' => $sale->id,
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'current_inventory_status' => $sale->inventory_status
        ]);

        // Status that should trigger inventory deduction
        $deductionStatuses = ['POD'];

        // Statuses that should restore inventory (with conditions)
        $restorationStatuses = ['Cancelled', 'Returned'];

        $inventoryUpdated = false;

        // If moving TO POD status - deduct inventory
        if (in_array($newStatus, $deductionStatuses) && $sale->inventory_status !== 'deducted') {
            Log::info('Attempting inventory deduction', [
                'sale_id' => $sale->id,
                'new_status' => $newStatus,
                'current_inventory_status' => $sale->inventory_status
            ]);

            $result = $this->deductInventory($sale);
            if (!$result['success']) {
                Log::error('Inventory deduction failed', [
                    'sale_id' => $sale->id,
                    'error' => $result['message']
                ]);
                return $result;
            }
            $inventoryUpdated = true;

            Log::info('Inventory deduction successful', [
                'sale_id' => $sale->id,
                'inventory_updated' => true
            ]);
        } else {
            Log::info('Skipping inventory deduction', [
                'sale_id' => $sale->id,
                'reason' => $sale->inventory_status === 'deducted' ? 'Already deducted' : 'Not POD status',
                'new_status' => $newStatus,
                'is_pod_status' => in_array($newStatus, $deductionStatuses),
                'current_inventory_status' => $sale->inventory_status
            ]);
        }

        // NEW LOGIC: Handle Cancelled/Returned status with POD consideration
        if (in_array($newStatus, $restorationStatuses) && $sale->inventory_status === 'deducted') {
            // Check if the previous status was POD
            if ($previousStatus === 'POD') {
                // If coming from POD, keep inventory deducted (delivered items should remain as completed transactions)
                Log::info('Keeping inventory deducted - sale was already delivered (POD)', [
                    'sale_id' => $sale->id,
                    'previous_status' => $previousStatus,
                    'new_status' => $newStatus,
                    'reason' => 'Items were delivered, treating cancellation/return as separate transaction'
                ]);

                // Update sale status to indicate this is a post-delivery cancellation/return
                $sale->update([
                    'inventory_status' => 'deducted_delivered', // New status to indicate delivered but cancelled/returned
                    'post_delivery_action' => $newStatus, // Track what action was taken after delivery
                    'post_delivery_action_at' => now()
                ]);

                
                // Note: Any return/cancellation of delivered items should be handled as a separate
                // return transaction or credit note, not by restoring the original sale inventory
            } else {
                // If NOT coming from POD, restore inventory (normal cancellation before delivery)
                Log::info('Restoring inventory - sale was not delivered yet', [
                    'sale_id' => $sale->id,
                    'previous_status' => $previousStatus,
                    'new_status' => $newStatus,
                    'reason' => 'Items were not delivered, safe to restore inventory'
                ]);

                $result = $this->restoreInventory($sale);
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
                'sale_id' => $sale->id,
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
    private function deductInventory(Sale $sale): array
    {
        try {
            Log::info('Starting inventory deduction for sale', [
                'sale_id' => $sale->id,
                'current_inventory_status' => $sale->inventory_status,
                'items_count' => $sale->itemTransaction->count()
            ]);

            // Check if sale has items
            if ($sale->itemTransaction->count() == 0) {
                Log::warning('No item transactions found for sale', ['sale_id' => $sale->id]);
                return ['success' => false, 'message' => 'No items found for inventory deduction'];
            }

            // Process inventory deduction for each item
            foreach ($sale->itemTransaction as $transaction) {
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

            // Update sale inventory status
            $sale->update([
                'inventory_status' => 'deducted',
                'inventory_deducted_at' => now()
            ]);

            Log::info('Inventory deduction completed for sale', [
                'sale_id' => $sale->id,
                'new_inventory_status' => 'deducted',
                'deducted_at' => now()
            ]);

            return ['success' => true, 'message' => 'Inventory deducted successfully'];

        } catch (Exception $e) {
            Log::error('Inventory deduction failed for sale', [
                'sale_id' => $sale->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return ['success' => false, 'message' => 'Failed to deduct inventory: ' . $e->getMessage()];
        }
    }

    /**
     * Restore inventory for Cancelled/Returned status
     */
    private function restoreInventory(Sale $sale): array
    {
        try {
            Log::info('Starting inventory restoration for sale', [
                'sale_id' => $sale->id,
                'items_count' => $sale->itemTransaction->count()
            ]);

            // Process inventory restoration for each item
            foreach ($sale->itemTransaction as $transaction) {
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

            // Update sale inventory status
            $sale->update([
                'inventory_status' => 'restored',
                'inventory_deducted_at' => null
            ]);

            Log::info('Inventory restoration completed for sale', ['sale_id' => $sale->id]);

            return ['success' => true, 'message' => 'Inventory restored successfully'];

        } catch (Exception $e) {
            Log::error('Inventory restoration failed for sale', [
                'sale_id' => $sale->id,
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
    private function canTransitionToStatus(Sale $sale, string $newStatus): bool
    {
        $currentStatus = $sale->sales_status;

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
    private function handleProofImageUpload($image, int $saleId, string $status): string
    {
        $directory = "sales/status_proofs/{$saleId}";
        $filename = $status . '_' . time() . '.' . $image->getClientOriginalExtension();

        return $image->storeAs($directory, $filename, 'public');
    }

    /**
     * Record status change in history
     */
    private function recordStatusHistory(Sale $sale, ?string $previousStatus, string $newStatus, ?string $notes, ?string $proofImage): void
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
                        'sale_id' => $sale->id,
                        'new_status' => $newStatus
                    ]);
                    return;
                }
            }

            Log::info('Status change recorded with fallback user', [
                'sale_id' => $sale->id,
                'new_status' => $newStatus,
                'fallback_user_id' => $changedBy
            ]);
        }

        SalesStatusHistory::create([
            'sale_id' => $sale->id,
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'notes' => $notes,
            'proof_image' => $proofImage,
            'changed_by' => $changedBy,
            'changed_at' => now(),
        ]);
    }

    /**
     * Get status history for a sale
     */
    public function getStatusHistory(Sale $sale): array
    {
        $histories = $sale->salesStatusHistories()
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
        return ['POD', 'Cancelled', 'Returned'];
    }
}
