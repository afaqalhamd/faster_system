<?php

namespace App\Services;

use App\Models\Sale\SaleReturn;
use App\Models\Sale\SaleOrder;
use App\Enums\ItemTransactionUniqueCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class SaleReturnService
{
    private $itemTransactionService;

    public function __construct(ItemTransactionService $itemTransactionService)
    {
        $this->itemTransactionService = $itemTransactionService;
    }

    /**
     * Create a sale return for a delivered order
     */
    public function createSaleReturn(SaleOrder $saleOrder, array $data = []): array
    {
        try {
            DB::beginTransaction();

            // Validate that this is a post-delivery return
            if ($saleOrder->inventory_status !== 'deducted_delivered') {
                throw new Exception('Sale return can only be created for delivered orders');
            }

            // Create the sale return
            $saleReturn = SaleReturn::create([
                'sale_order_id' => $saleOrder->id,
                'return_reason' => $data['return_reason'] ?? null,
                'notes' => $data['notes'] ?? null,
                'return_date' => now(),
            ]);

            // Process inventory for each item
            foreach ($saleOrder->itemTransaction as $transaction) {
                // Update the transaction unique code from SALE to RETURN
                $transaction->update([
                    'unique_code' => ItemTransactionUniqueCode::RETURN->value
                ]);

                // Update inventory quantities
                $this->itemTransactionService->updateItemGeneralQuantityWarehouseWise($transaction->item_id);

                // Handle batch/serial tracking if applicable
                if ($transaction->tracking_type === 'batch') {
                    $this->updateBatchInventoryAfterReturn($transaction);
                } elseif ($transaction->tracking_type === 'serial') {
                    $this->updateSerialInventoryAfterReturn($transaction);
                }
            }

            // Update sale order to indicate return was processed
            $saleOrder->update([
                'post_delivery_return_processed' => true,
                'post_delivery_return_processed_at' => now()
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Sale return processed successfully',
                'sale_return' => $saleReturn
            ];

        } catch (Exception $e) {
            DB::rollback();
            Log::error('Sale return failed', [
                'sale_order_id' => $saleOrder->id,
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'message' => 'Failed to process sale return: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update batch inventory after return
     */
    private function updateBatchInventoryAfterReturn($transaction)
    {
        foreach ($transaction->itemBatchTransactions as $batchTransaction) {
            $batchTransaction->update([
                'unique_code' => ItemTransactionUniqueCode::RETURN->value
            ]);

            $this->itemTransactionService->updateItemBatchQuantityWarehouseWise(
                $batchTransaction->item_batch_master_id
            );
        }
    }

    /**
     * Update serial inventory after return
     */
    private function updateSerialInventoryAfterReturn($transaction)
    {
        foreach ($transaction->itemSerialTransaction as $serialTransaction) {
            $serialTransaction->update([
                'unique_code' => ItemTransactionUniqueCode::RETURN->value
            ]);

            $this->itemTransactionService->updateItemSerialCurrentStatusWarehouseWise(
                $serialTransaction->item_serial_master_id
            );
        }
    }
}
