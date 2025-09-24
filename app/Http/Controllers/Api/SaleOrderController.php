<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale\SaleOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Services\PaymentTransactionService;
use App\Services\SaleOrderStatusService;

class SaleOrderController extends Controller
{
    protected $paymentTransactionService;
    protected $saleOrderStatusService;

    /**
     * Create a new controller instance.
     *
     * @param PaymentTransactionService $paymentTransactionService
     * @return void
     */
    public function __construct(PaymentTransactionService $paymentTransactionService, SaleOrderStatusService $saleOrderStatusService)
    {
        $this->paymentTransactionService = $paymentTransactionService;
        $this->saleOrderStatusService = $saleOrderStatusService;
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

    /**
     * Display a listing of sale orders for delivery users.
     * Applies carrier filtering based on user's carrier assignment.
     * Includes pagination for mobile performance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deliveryOrders(Request $request)
    {
        $user = auth()->user();

        // Check if user is a delivery user with carrier assignment
        if (!$user || !$user->carrier_id || !$user->role || strtolower($user->role->name) !== 'delivery') {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access. User must be a delivery user with carrier assignment.'
            ], 403);
        }

        $query = SaleOrder::with(['party', 'carrier'])
            ->where('carrier_id', $user->carrier_id)
            ->whereIn('order_status', ['Delivery', 'POD', 'Returned', 'Cancelled']);

        // Apply status filter if provided
        if ($request->has('status')) {
            $query->where('order_status', $request->status);
        }

        // Apply date range filters if provided
        if ($request->has('date_from')) {
            $query->where('order_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('order_date', '<=', $request->date_to);
        }

        // Implement pagination
        $perPage = $request->get('per_page', 15);
        $saleOrders = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $saleOrders
        ]);
    }

    /**
     * Display the specified sale order with delivery details.
     * Optimized for mobile performance with selective field loading.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deliveryOrderDetails($id)
    {
        $user = auth()->user();

        // Check if user is a delivery user with carrier assignment
        if (!$user || !$user->carrier_id || !$user->role || strtolower($user->role->name) !== 'delivery') {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access. User must be a delivery user with carrier assignment.'
            ], 403);
        }

        // Optimize query for mobile by selecting only necessary fields
        $saleOrder = SaleOrder::select([
                'id', 'order_code', 'order_date', 'due_date', 'grand_total',
                'paid_amount', 'order_status', 'party_id', 'carrier_id',
                'created_at', 'updated_at'
            ])
            ->with([
                'party:id,first_name,last_name,shipping_address,phone,email',
                'itemTransaction:id,transaction_id,transaction_type,item_id,quantity,unit_price,total',
                'itemTransaction.item:id,name,sku',
                'carrier:id,name'
            ])
            ->where('carrier_id', $user->carrier_id)
            ->whereIn('order_status', ['Delivery', 'POD', 'Returned', 'Cancelled'])
            ->find($id);

        if (!$saleOrder) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sale order not found or not assigned to your carrier'
            ], 404);
        }

        // Get payment records array
        $paymentRecords = $this->paymentTransactionService->getPaymentRecordsArray($saleOrder);

        return response()->json([
            'status' => 'success',
            'data' => [
                'order' => $saleOrder,
                'payment_records' => $paymentRecords
            ]
        ]);
    }

    /**
     * Get delivery user profile with carrier information
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function deliveryProfile()
    {
        $user = auth()->user();

        if (!$user || !$user->carrier_id || !$user->role || strtolower($user->role->name) !== 'delivery') {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access. User must be a delivery user with carrier assignment.'
            ], 403);
        }

        // Create response data with user and carrier information
        $responseData = [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'username' => $user->username,
            'email' => $user->email,
            'role_id' => $user->role_id,
            'carrier_id' => $user->carrier_id,
            'status' => $user->status,
            'avatar' => $user->avatar,
            'mobile' => $user->mobile,
            'is_allowed_all_warehouses' => $user->is_allowed_all_warehouses,
            'fc_token' => $user->fc_token,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
            'carrier' => $user->carrier
        ];

        return response()->json([
            'status' => 'success',
            'data' => $responseData
        ]);
    }

    /**
     * Get valid delivery statuses
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function deliveryStatuses()
    {
        $statuses = [
            ['id' => 'Delivery', 'name' => 'Delivery', 'description' => 'Order is out for delivery'],
            ['id' => 'POD', 'name' => 'POD', 'description' => 'Proof of delivery collected'],
            ['id' => 'Cancelled', 'name' => 'Cancelled', 'description' => 'Order cancelled'],
            ['id' => 'Returned', 'name' => 'Returned', 'description' => 'Order returned']
        ];

        return response()->json([
            'status' => 'success',
            'data' => $statuses
        ]);
    }

    /**
     * Update sale order status for delivery operations
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateDeliveryStatus(Request $request, $id)
    {
        $user = auth()->user();

        // Check if user is a delivery user with carrier assignment
        if (!$user || !$user->carrier_id || !$user->role || strtolower($user->role->name) !== 'delivery') {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access. User must be a delivery user with carrier assignment.'
            ], 403);
        }

        // Find the sale order with carrier filtering
        $saleOrder = SaleOrder::where('carrier_id', $user->carrier_id)
            ->whereIn('order_status', ['Delivery', 'POD', 'Returned', 'Cancelled'])
            ->find($id);

        if (!$saleOrder) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sale order not found or not assigned to your carrier'
            ], 404);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:Delivery,POD,Cancelled,Returned',
            'notes' => 'nullable|string|max:500',
            'proof_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Use the SaleOrderStatusService to update the status
        $result = $this->saleOrderStatusService->updateSaleOrderStatus(
            $saleOrder,
            $request->status,
            [
                'notes' => $request->notes,
                'proof_image' => $request->file('proof_image')
            ]
        );

        if ($result['success']) {
            return response()->json([
                'status' => 'success',
                'message' => $result['message']
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => $result['message']
            ], 400);
        }
    }

    /**
     * Get status history for a delivery order
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deliveryOrderStatusHistory($id)
    {
        $user = auth()->user();

        // Check if user is a delivery user with carrier assignment
        if (!$user || !$user->carrier_id || !$user->role || strtolower($user->role->name) !== 'delivery') {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access. User must be a delivery user with carrier assignment.'
            ], 403);
        }

        // Find the sale order with carrier filtering
        $saleOrder = SaleOrder::where('carrier_id', $user->carrier_id)
            ->whereIn('order_status', ['Delivery', 'POD', 'Returned', 'Cancelled'])
            ->with('saleOrderStatusHistories.changedBy')
            ->find($id);

        if (!$saleOrder) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sale order not found or not assigned to your carrier'
            ], 404);
        }

        // Use the SaleOrderStatusService to get the status history
        $history = $this->saleOrderStatusService->getStatusHistory($saleOrder);

        return response()->json([
            'status' => 'success',
            'data' => $history
        ]);
    }

    /**
     * Collect payment for a delivery order
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function collectDeliveryPayment(Request $request, $id)
    {
        $user = auth()->user();

        // Check if user is a delivery user with carrier assignment
        if (!$user || !$user->carrier_id || !$user->role || strtolower($user->role->name) !== 'delivery') {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access. User must be a delivery user with carrier assignment.'
            ], 403);
        }

        // Find the sale order with carrier filtering
        $saleOrder = SaleOrder::where('carrier_id', $user->carrier_id)
            ->whereIn('order_status', ['Delivery', 'POD', 'Returned', 'Cancelled'])
            ->find($id);

        if (!$saleOrder) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sale order not found or not assigned to your carrier'
            ], 404);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0',
            'payment_type_id' => 'required|exists:payment_types,id',
            'note' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Record the payment
            $paymentData = [
                'transaction_date' => now()->format('Y-m-d'),
                'amount' => $request->amount,
                'payment_type_id' => $request->payment_type_id,
                'note' => $request->note ?? '',
                'payment_from_unique_code' => \App\Enums\General::INVOICE->value,
            ];

            $payment = $this->paymentTransactionService->recordPayment($saleOrder, $paymentData);

            if (!$payment) {
                throw new \Exception(__('payment.failed_to_record_payment_transactions'));
            }

            // Update total paid amount in the sale order model
            $this->paymentTransactionService->updateTotalPaidAmountInModel($saleOrder);

            return response()->json([
                'status' => 'success',
                'message' => 'Payment collected successfully',
                'data' => [
                    'payment' => $payment,
                    'updated_order' => $saleOrder->refresh()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to collect payment: ' . $e->getMessage()
            ], 500);
        }
    }
}
