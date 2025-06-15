<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale\SaleOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Services\PaymentTransactionService;

class SaleOrderController extends Controller
{
    protected $paymentTransactionService;

    /**
     * Create a new controller instance.
     *
     * @param PaymentTransactionService $paymentTransactionService
     * @return void
     */
    public function __construct(PaymentTransactionService $paymentTransactionService)
    {
        $this->paymentTransactionService = $paymentTransactionService;
    }

    /**
     * Display a listing of sale orders.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $saleOrders = SaleOrder::with(['user', 'party', 'itemTransaction'])->get();

        return response()->json([
            'status' => 'success',
            'data' => $saleOrders
        ]);
    }

    /**
     * Store a newly created sale order in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_date' => 'required|date',
            'due_date' => 'required|date',
            'party_id' => 'required|exists:parties,id',
            'grand_total' => 'required|numeric',
            'currency_id' => 'required|exists:currencies,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $saleOrder = SaleOrder::create($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Sale order created successfully',
            'data' => $saleOrder
        ], 201);
    }
/**
     * Display a listing of sale orders created in the last 24 hours.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRecentSaleOrders()
    {
        $saleOrders = SaleOrder::with(['user', 'party', 'itemTransaction'])
                    ->where('created_at', '>=', now()->subHours(24))
                    ->get();

        return response()->json([
            'status' => 'success',
            'data' => $saleOrders
        ]);
    }
    /**
     * Display the specified sale order.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $saleOrder = SaleOrder::with(['user', 'party', 'itemTransaction', 'statusHistory'])->find($id);

        if (!$saleOrder) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sale order not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $saleOrder
        ]);
    }

    /**
     * Update the specified sale order in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $saleOrder = SaleOrder::find($id);

        if (!$saleOrder) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sale order not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'order_date' => 'date',
            'due_date' => 'date',
            'party_id' => 'exists:parties,id',
            'grand_total' => 'numeric',
            'currency_id' => 'exists:currencies,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $saleOrder->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Sale order updated successfully',
            'data' => $saleOrder
        ]);
    }

    /**
     * Remove the specified sale order from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $saleOrder = SaleOrder::find($id);

        if (!$saleOrder) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sale order not found'
            ], 404);
        }

        $saleOrder->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Sale order deleted successfully'
        ]);
    }

    /**
     * Display the specified sale order with detailed relationships.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function details($id)
    {
        $order = SaleOrder::with(['party',
            'user',
            'itemTransaction' => [
                'item',
                'tax',
                'batch.itemBatchMaster',
                'itemSerialTransaction.itemSerialMaster'
            ],
            'statusHistory',
            'paymentTransaction'
        ])->find($id);

        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sale order not found'
            ], 404);
        }

        // Get payment records array
        $paymentRecords = $this->paymentTransactionService->getPaymentRecordsArray($order);

        return response()->json([
            'status' => 'success',
            'data' => [
                'order' => $order,
                'payment_records' => $paymentRecords
            ]
        ]);
    }

    /**
     * Convert a sale order to a sale invoice.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function convertToSale($id)
    {
        // Check if the sale order has already been converted
        $convertedBill = \App\Models\Sale\Sale::where('sale_order_id', $id)->first();

        if ($convertedBill) {
            return response()->json([
                'status' => 'info',
                'message' => 'Sale order already converted to invoice',
                'data' => [
                    'sale_id' => $convertedBill->id
                ]
            ]);
        }

        // Find the sale order with related data
        $saleOrder = SaleOrder::with(['party',
            'itemTransaction' => [
                'item',
                'tax',
                'batch.itemBatchMaster',
                'itemSerialTransaction.itemSerialMaster'
            ]])->find($id);

        if (!$saleOrder) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sale order not found'
            ], 404);
        }

        try {
            // Begin transaction
            \Illuminate\Support\Facades\DB::beginTransaction();

            // Get prefix and count ID for new sale
            $prefix = \App\Models\Prefix::findOrNew(1); // Using 1 as company ID, adjust if needed
            $lastCountId = \App\Models\Sale\Sale::select('count_id')->orderBy('id', 'desc')->first()?->count_id ?? 0;

            // Create new sale from sale order
            $newSale = new \App\Models\Sale\Sale();
            $newSale->party_id = $saleOrder->party_id;
            $newSale->sale_date = date('Y-m-d');
            $newSale->prefix_code = $prefix->sale;
            $newSale->count_id = ($lastCountId + 1);
            $newSale->sale_code = $prefix->sale . ($lastCountId + 1);
            $newSale->grand_total = $saleOrder->grand_total;
            $newSale->sale_order_id = $saleOrder->id;
            $newSale->currency_id = $saleOrder->currency_id;
            $newSale->exchange_rate = $saleOrder->exchange_rate;
            $newSale->state_id = $saleOrder->state_id;
            $newSale->user_id = auth()->id();
            $newSale->save();

            // Copy item transactions from sale order to sale
            foreach ($saleOrder->itemTransaction as $transaction) {
                $newTransaction = $transaction->replicate();
                $newTransaction->sale_id = $newSale->id;
                $newTransaction->sale_order_id = null;
                $newTransaction->save();

                // Copy batch transactions if any
                if ($transaction->batch) {
                    $newBatch = $transaction->batch->replicate();
                    $newBatch->item_transaction_id = $newTransaction->id;
                    $newBatch->save();
                }

                // Copy serial transactions if any
                foreach ($transaction->itemSerialTransaction as $serialTransaction) {
                    $newSerialTransaction = $serialTransaction->replicate();
                    $newSerialTransaction->item_transaction_id = $newTransaction->id;
                    $newSerialTransaction->save();
                }
            }

            \Illuminate\Support\Facades\DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Sale order converted to invoice successfully',
                'data' => [
                    'sale_id' => $newSale->id,
                    'sale_code' => $newSale->sale_code
                ]
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to convert sale order: ' . $e->getMessage()
            ], 500);
        }
    }
}