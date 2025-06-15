<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Items\ItemTransaction;
use App\Services\StockImpact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ItemTransactionController extends Controller
{
    protected $stockImpact;

    /**
     * Create a new controller instance.
     *
     * @param StockImpact $stockImpact
     * @return void
     */
    public function __construct(StockImpact $stockImpact)
    {
        $this->stockImpact = $stockImpact;
    }

    /**
     * Display a listing of item transactions with pagination.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 20);

        $transactions = ItemTransaction::with(['item', 'warehouse', 'unit', 'tax'])
                        ->orderBy('created_at', 'desc')
                        ->paginate($perPage);

        $data = $transactions->items();

        // Add stock_impact to each transaction
        foreach ($data as $transaction) {
            $transaction->stock_impact = $this->stockImpact->returnStockImpact($transaction->unique_code, $transaction->quantity);
        }

        return response()->json([
            'status' => 'success',
            'data' => $data,
            'pagination' => [
                'total' => $transactions->total(),
                'per_page' => $transactions->perPage(),
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'from' => $transactions->firstItem(),
                'to' => $transactions->lastItem(),
            ]
        ]);
    }

    /**
     * Display the specified item transaction.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $transaction = ItemTransaction::with([
            'item',
            'warehouse',
            'unit',
            'tax',
            'itemBatchTransactions',
            'itemSerialTransaction'
        ])->find($id);

        if (!$transaction) {
            return response()->json([
                'status' => 'error',
                'message' => 'Item transaction not found'
            ], 404);
        }

        // Add stock_impact
        $transaction->stock_impact = $this->stockImpact->returnStockImpact($transaction->unique_code, $transaction->quantity);

        return response()->json([
            'status' => 'success',
            'data' => $transaction
        ]);
    }

    /**
     * Get transactions by item ID.
     *
     * @param  int  $itemId
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTransactionsByItem($itemId, Request $request)
    {
        $perPage = $request->input('per_page', 20);

        $transactions = ItemTransaction::with(['warehouse', 'unit', 'tax'])
                        ->where('item_id', $itemId)
                        ->orderBy('created_at', 'desc')
                        ->paginate($perPage);

        $data = $transactions->items();

        // Add stock_impact to each transaction
        foreach ($data as $transaction) {
            $transaction->stock_impact = $this->stockImpact->returnStockImpact($transaction->unique_code, $transaction->quantity);
        }

        return response()->json([
            'status' => 'success',
            'data' => $data,
            'pagination' => [
                'total' => $transactions->total(),
                'per_page' => $transactions->perPage(),
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'from' => $transactions->firstItem(),
                'to' => $transactions->lastItem(),
            ]
        ]);
    }

    /**
     * Get transactions by warehouse ID.
     *
     * @param  int  $warehouseId
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTransactionsByWarehouse($warehouseId, Request $request)
    {
        $perPage = $request->input('per_page', 20);

        $transactions = ItemTransaction::with(['item', 'unit', 'tax'])
                        ->where('warehouse_id', $warehouseId)
                        ->orderBy('created_at', 'desc')
                        ->paginate($perPage);

        $data = $transactions->items();

        // Add stock_impact to each transaction
        foreach ($data as $transaction) {
            $transaction->stock_impact = $this->stockImpact->returnStockImpact($transaction->unique_code, $transaction->quantity);
        }

        return response()->json([
            'status' => 'success',
            'data' => $data,
            'pagination' => [
                'total' => $transactions->total(),
                'per_page' => $transactions->perPage(),
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'from' => $transactions->firstItem(),
                'to' => $transactions->lastItem(),
            ]
        ]);
    }

    /**
     * Get recent transactions (last 24 hours).
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRecentTransactions(Request $request)
    {
        $perPage = $request->input('per_page', 20);

        $transactions = ItemTransaction::with(['item', 'warehouse', 'unit', 'tax'])
                        ->where('created_at', '>=', now()->subHours(24))
                        ->orderBy('created_at', 'desc')
                        ->paginate($perPage);

        $data = $transactions->items();

        // Add stock_impact to each transaction
        foreach ($data as $transaction) {
            $transaction->stock_impact = $this->stockImpact->returnStockImpact($transaction->unique_code, $transaction->quantity);
        }

        return response()->json([
            'status' => 'success',
            'data' => $data,
            'pagination' => [
                'total' => $transactions->total(),
                'per_page' => $transactions->perPage(),
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'from' => $transactions->firstItem(),
                'to' => $transactions->lastItem(),
            ]
        ]);
    }
}