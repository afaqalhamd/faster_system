<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Items\Item;
use App\Models\Items\ItemGeneralQuantity;
use App\Models\Items\ItemTransaction;
use App\Services\ItemTransactionService;
use App\Services\StockImpact;
use App\Traits\FormatNumber;
use App\Traits\FormatsDateInputs;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class StockReportController extends Controller
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
     * Get products that have been moved out in the last 24 hours
     *
     * @return JsonResponse
     */
    public function getProductsMovedOutLast24Hours(): JsonResponse
    {
        try {
            // Get transactions from the last 24 hours with negative impact (moved out)
            $transactions = ItemTransaction::with(['item', 'warehouse'])
                ->where('created_at', '>=', Carbon::now()->subHours(24))
                ->where('quantity', '<', 0)
                ->get();

            if ($transactions->count() == 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'No products moved out in the last 24 hours'
                ], 404);
            }

            $recordsArray = [];

            foreach ($transactions as $transaction) {
                $recordsArray[] = [
                    'transaction_date' => $this->toUserDateFormat($transaction->created_at),
                    'transaction_time' => Carbon::parse($transaction->created_at)->format('H:i:s'),
                    'item_name' => $transaction->item->name,
                    'warehouse' => $transaction->warehouse->name ?? 'N/A',
                    'quantity' => $this->formatWithPrecision(abs($transaction->quantity), comma: false),
                    'unit_name' => $transaction->item->baseUnit->name ?? 'N/A',
                    'transaction_type' => $transaction->transaction_type,
                ];
            }

            return response()->json([
                'status' => true,
                'message' => 'Products moved out in the last 24 hours retrieved successfully',
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
     * Get products with zero quantity (out of stock)
     *
     * @return JsonResponse
     */
    public function getOutOfStockProducts(): JsonResponse
    {
        try {
            $outOfStockItems = ItemGeneralQuantity::with(['item', 'warehouse'])
                ->where('quantity', '<=', 0)
                ->get();

            if ($outOfStockItems->count() == 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'No out of stock products found'
                ], 404);
            }

            $recordsArray = [];

            foreach ($outOfStockItems as $item) {
                $recordsArray[] = [
                    'warehouse' => $item->warehouse->name,
                    'item' => $item->item,
                    'brand_name' => $item->item->brand->name ?? '',
                    'category_name' => $item->item->category->name ?? '',
                    'quantity' => $this->formatWithPrecision($item->quantity, comma: false),
                    'unit_name' => $item->item->baseUnit->name ?? '',
                    'alert_quantity' => $this->formatWithPrecision($item->item->alert_quantity ?? 0, comma: false),
                ];
            }

            return response()->json([
                'status' => true,
                'message' => 'Out of stock products retrieved successfully',
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
     * Get products with increased quantity in the last 24 hours
     *
     * @return JsonResponse
     */
    public function getProductsWithIncreasedQuantity(): JsonResponse
    {
        try {
            // Get transactions from the last 24 hours with positive impact (increased quantity)
            $transactions = ItemTransaction::with(['item', 'warehouse'])
                ->where('created_at', '>=', Carbon::now()->subHours(24))
                ->where('quantity', '>', 0)
                ->where(function($query) {
                    // Include only transaction types that represent items moving IN
                    $query->where('transaction_type', 'Purchase')
                          ->orWhere('transaction_type', 'Sale Return')
                          ->orWhere('transaction_type', 'Stock Transfer	')
                          ->orWhere('transaction_type', 'Item Opening');
                })
                ->get();

            if ($transactions->count() == 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'No products with increased quantity in the last 24 hours'
                ], 404);
            }

            $recordsArray = [];

            foreach ($transactions as $transaction) {
                $recordsArray[] = [
                    'transaction_date' => $this->toUserDateFormat($transaction->created_at),
                    'transaction_time' => Carbon::parse($transaction->created_at)->format('H:i:s'),
                    'item' => $transaction->item,
                    'warehouse' => $transaction->warehouse->name ?? 'N/A',
                    'quantity' => $this->formatWithPrecision($transaction->quantity, comma: false),
                    'unit_name' => $transaction->item->baseUnit->name ?? 'N/A',
                    'transaction_type' => $transaction->transaction_type,
                ];
            }

            return response()->json([
                'status' => true,
                'message' => 'Products with increased quantity retrieved successfully',
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