<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Items\ItemTransaction;
use App\Services\ItemTransactionService;
use App\Services\StockImpact;
use App\Traits\FormatNumber;
use App\Traits\FormatsDateInputs;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class ItemTransactionReportController extends Controller
{
    use FormatsDateInputs;
    use FormatNumber;

    private $stockImpact;
    private $itemTransactionService;

    function __construct(StockImpact $stockImpact, ItemTransactionService $itemTransactionService)
    {
        $this->stockImpact = $stockImpact;
        $this->itemTransactionService = $itemTransactionService;
    }

    /**
     * Get items that have been moved in during the last 24 hours without filters
     *
     * @return JsonResponse
     */
    function getItemsMovedInLast24Hours(): JsonResponse
    {
        try {
            // Get transactions from the last 24 hours with positive impact (moved in)
            $transactions = ItemTransaction::with(['item.brand', 'warehouse', 'transaction'])
                ->where('created_at', '>=', Carbon::now()->subHours(24))
                ->where('quantity', '>', 0)
                ->get();

            if ($transactions->count() == 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'No items moved in during the last 24 hours'
                ], 404);
            }

            $recordsArray = [];

            foreach ($transactions as $transaction) {
                $recordsArray[] = [
                    'transaction_date' => $this->toUserDateFormat($transaction->transaction_date),
                    'transaction_time' => Carbon::parse($transaction->created_at)->format('H:i:s'),
                    'transaction_type' => $transaction->transaction_type,
                    'invoice_or_bill_code' => $transaction->transaction ? $transaction->transaction->getTableCode() : '',
                    'party_name' => $transaction->transaction && method_exists($transaction->transaction, 'party') && $transaction->transaction->party ? $transaction->transaction->party->getFullName() : '',
                    'warehouse' => $transaction->warehouse->name ?? 'N/A',
                    'item_name' => $transaction->item->name,
                    'brand_name' => $transaction->item->brand->name ?? '',
                    'quantity' => $this->formatWithPrecision($transaction->quantity, comma: false),
                    'unit_name' => $transaction->item->baseUnit->name ?? 'N/A',
                    'stock_impact' => $this->stockImpact->returnStockImpact($transaction->unique_code, $transaction->quantity)['quantity'],
                    'stock_impact_color' => $this->stockImpact->returnStockImpact($transaction->unique_code, $transaction->quantity)['color'],
                ];
            }

            return response()->json([
                'status' => true,
                'message' => 'Items moved in during the last 24 hours retrieved successfully',
                'data' => $recordsArray,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get items that have been moved out during the last 24 hours without filters
     *
     * @return JsonResponse
     */
    function getItemsMovedOutLast24Hours(): JsonResponse
    {
        try {
            // Get transactions from the last 24 hours with negative impact (moved out)
            $transactions = ItemTransaction::with(['item.brand', 'warehouse', 'transaction'])
                ->where('created_at', '>=', Carbon::now()->subHours(24))
                ->where('quantity', '<', 0)
                ->get();

            if ($transactions->count() == 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'No items moved out during the last 24 hours'
                ], 404);
            }

            $recordsArray = [];

            foreach ($transactions as $transaction) {
                $recordsArray[] = [
                    'transaction_date' => $this->toUserDateFormat($transaction->transaction_date),
                    'transaction_time' => Carbon::parse($transaction->created_at)->format('H:i:s'),
                    'transaction_type' => $transaction->transaction_type,
                    'invoice_or_bill_code' => $transaction->transaction ? $transaction->transaction->getTableCode() : '',
                    'party_name' => $transaction->transaction && method_exists($transaction->transaction, 'party') && $transaction->transaction->party ? $transaction->transaction->party->getFullName() : '',
                    'warehouse' => $transaction->warehouse->name ?? 'N/A',
                    'item_name' => $transaction->item->name,
                    'brand_name' => $transaction->item->brand->name ?? '',
                    'quantity' => $this->formatWithPrecision(abs($transaction->quantity), comma: false),
                    'unit_name' => $transaction->item->baseUnit->name ?? 'N/A',
                    'stock_impact' => $this->stockImpact->returnStockImpact($transaction->unique_code, $transaction->quantity)['quantity'],
                    'stock_impact_color' => $this->stockImpact->returnStockImpact($transaction->unique_code, $transaction->quantity)['color'],
                ];
            }

            return response()->json([
                'status' => true,
                'message' => 'Items moved out during the last 24 hours retrieved successfully',
                'data' => $recordsArray,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}