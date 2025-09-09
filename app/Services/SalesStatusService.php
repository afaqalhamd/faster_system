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
        // Status that should trigger inventory deduction
        $deductionStatuses = ['POD'];

        // Statuses that should restore inventory
        $restorationStatuses = ['Cancelled', 'Returned'];

        $inventoryUpdated = false;

        // If moving TO POD status - deduct inventory
        if (in_array($newStatus, $deductionStatuses) && $sale->inventory_status !== 'deducted') {
            $result = $this->deductInventory($sale);
            if (!$result['success']) {
                return $result;
            }
            $inventoryUpdated = true;
        }

        // If moving TO Cancelled/Returned status - restore inventory (only if it was deducted)
        if (in_array($newStatus, $restorationStatuses) && $sale->inventory_status === 'deducted') {
            $result = $this->restoreInventory($sale);
            if (!$result['success']) {
                return $result;
            }
            $inventoryUpdated = true;
        }

        // If moving FROM POD to other status (except Cancelled/Returned) - keep inventory deducted
        if ($previousStatus === 'POD' && !in_array($newStatus, array_merge($deductionStatuses, $restorationStatuses))) {
            // Keep inventory deducted, no action needed
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
                'items_count' => $sale->itemTransaction->count()
            ]);

            // Process inventory deduction for each item
            foreach ($sale->itemTransaction as $transaction) {
                // Update the transaction unique code from SALE_ORDER to SALE
                $transaction->update([
                    'unique_code' => ItemTransactionUniqueCode::SALE->value
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

            Log::info('Inventory deduction completed for sale', ['sale_id' => $sale->id]);

            return ['success' => true, 'message' => 'Inventory deducted successfully'];

        } catch (Exception $e) {
            Log::error('Inventory deduction failed for sale', [
                'sale_id' => $sale->id,
                'error' => $e->getMessage()
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
            'Pending' => ['Processing', 'Completed', 'Delivery', 'Cancelled'],
            'Processing' => ['Completed', 'Delivery', 'Cancelled'],
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
        SalesStatusHistory::create([
            'sale_id' => $sale->id,
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'notes' => $notes,
            'proof_image' => $proofImage,
            'changed_by' => auth()->id(),
            'changed_at' => now(),
        ]);
    }

    /**
     * Get status history for a sale
     */
    public function getStatusHistory(Sale $sale): array
    {
        return $sale->salesStatusHistories()
            ->with('changedBy')
            ->orderBy('changed_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Get statuses that require proof image and notes
     */
    public function getStatusesRequiringProof(): array
    {
        return ['POD', 'Cancelled', 'Returned'];
    }
}
