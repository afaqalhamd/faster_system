<?php

namespace App\Http\Controllers\Api\Delivery;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Sale\SaleOrder;
use App\Http\Resources\DeliveryOrderResource;
use App\Http\Resources\DeliveryOrderDetailResource;
use App\Services\SaleOrderStatusService;
use App\Services\PaymentTransactionService;
use App\Http\Requests\DeliveryOrderStatusRequest;
use App\Http\Requests\DeliveryPaymentRequest;
use App\Http\Requests\DeliveryOrderUpdateRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    protected $saleOrderStatusService;
    protected $paymentTransactionService;

    public function __construct(
        SaleOrderStatusService $saleOrderStatusService,
        PaymentTransactionService $paymentTransactionService
    ) {
        $this->saleOrderStatusService = $saleOrderStatusService;
        $this->paymentTransactionService = $paymentTransactionService;
    }

    /**
     * Get delivery orders for the authenticated delivery user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            // Validate user is delivery personnel
            if (!$this->isDeliveryUser($user)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            // Build query with carrier filtering
            $query = SaleOrder::with(['party', 'carrier'])
                ->where('carrier_id', $user->carrier_id)
                ->whereIn('order_status', ['Delivery', 'POD', 'Returned', 'Cancelled']);

            // Apply filters
            if ($request->has('status')) {
                $query->where('order_status', $request->status);
            }

            if ($request->has('date_from')) {
                $query->where('order_date', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->where('order_date', '<=', $request->date_to);
            }

            // Apply search
            if ($request->has('search')) {
                $searchTerm = $request->search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('order_code', 'like', "%{$searchTerm}%")
                        ->orWhereHas('party', function ($partyQuery) use ($searchTerm) {
                            $partyQuery->where('first_name', 'like', "%{$searchTerm}%")
                                ->orWhere('last_name', 'like', "%{$searchTerm}%");
                        });
                });
            }

            // Paginate results - Order by created_at to show newest orders first
            $orders = $query->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 15));

            return response()->json([
                'status' => true,
                'data' => DeliveryOrderResource::collection($orders),
                'pagination' => [
                    'current_page' => $orders->currentPage(),
                    'last_page' => $orders->lastPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve orders: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get order details
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        try {
            $user = Auth::user();

            // Validate user is delivery personnel
            if (!$this->isDeliveryUser($user)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            // Find order with carrier validation
            $order = SaleOrder::with([
                'party',
                'carrier',
                'itemTransaction.item',
                'itemTransaction.tax',
                'paymentTransaction.paymentType'
            ])->where('carrier_id', $user->carrier_id)
              ->findOrFail($id);

            return response()->json([
                'status' => true,
                'data' => new DeliveryOrderDetailResource($order)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve order details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update order details (limited fields for delivery users)
     *
     * @param DeliveryOrderUpdateRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(DeliveryOrderUpdateRequest $request, $id): JsonResponse
    {
        try {
            $user = Auth::user();

            // Validate user is delivery personnel
            if (!$this->isDeliveryUser($user)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            // Find order with carrier validation
            $order = SaleOrder::where('carrier_id', $user->carrier_id)
                              ->findOrFail($id);

            // Only allow delivery users to update specific fields
            $updateData = $request->only([
                'note',
                'shipping_charge',
                'is_shipping_charge_distributed'
            ]);

            // Update the order
            $order->update($updateData);

            return response()->json([
                'status' => true,
                'message' => 'Order updated successfully',
                'data' => new DeliveryOrderDetailResource($order->refresh())
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update order status with proof
     *
     * @param DeliveryOrderStatusRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateStatus(DeliveryOrderStatusRequest $request, $id): JsonResponse
    {
        try {
            $user = Auth::user();

            // Validate user is delivery personnel
            if (!$this->isDeliveryUser($user)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            // Find order with carrier validation
            $order = SaleOrder::where('carrier_id', $user->carrier_id)
                              ->findOrFail($id);

            // Update status using existing service
            $result = $this->saleOrderStatusService->updateSaleOrderStatus(
                $order,
                $request->status,
                [
                    'notes' => $request->notes,
                    'signature' => $request->signature,
                    'proof_images' => $request->photos,
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude,
                    'changed_by' => $user->id
                ]
            );

            if ($result['success']) {
                return response()->json([
                    'status' => true,
                    'message' => 'Status updated successfully',
                    'data' => [
                        'order_id' => $order->id,
                        'order_code' => $order->order_code,
                        'status' => $request->status
                    ]
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => $result['message']
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Collect payment for order
     *
     * @param DeliveryPaymentRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function collectPayment(DeliveryPaymentRequest $request, $id): JsonResponse
    {
        try {
            $user = Auth::user();

            // Validate user is delivery personnel
            if (!$this->isDeliveryUser($user)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            // Find order with carrier validation
            $order = SaleOrder::where('carrier_id', $user->carrier_id)
                              ->findOrFail($id);

            // Record payment using existing service
            $paymentData = [
                'transaction_date' => now()->format('Y-m-d'),
                'amount' => $request->amount,
                'payment_type_id' => $request->payment_type_id,
                'note' => $request->notes,
                'reference_number' => $request->reference_number
            ];

            $payment = $this->paymentTransactionService->recordPayment($order, $paymentData);

            if ($payment) {
                // Update total paid amount
                $this->paymentTransactionService->updateTotalPaidAmountInModel($order);

                return response()->json([
                    'status' => true,
                    'message' => 'Payment collected successfully',
                    'data' => [
                        'payment_id' => $payment->id,
                        'amount' => $request->amount,
                        'order_id' => $order->id,
                        'order_code' => $order->order_code,
                        'balance' => $order->grand_total - $order->paid_amount
                    ]
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Failed to record payment'
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to collect payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get order status history
     *
     * @param int $id
     * @return JsonResponse
     */
    public function statusHistory($id): JsonResponse
    {
        try {
            $user = Auth::user();

            // Validate user is delivery personnel
            if (!$this->isDeliveryUser($user)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            // Find order with carrier validation
            $order = SaleOrder::where('carrier_id', $user->carrier_id)
                              ->with('saleOrderStatusHistories.changedBy')
                              ->findOrFail($id);

            return response()->json([
                'status' => true,
                'data' => $order->saleOrderStatusHistories
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve status history: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get order payment history
     *
     * @param int $id
     * @return JsonResponse
     */
    public function paymentHistory($id): JsonResponse
    {
        try {
            $user = Auth::user();

            // Validate user is delivery personnel
            if (!$this->isDeliveryUser($user)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            // Find order with carrier validation
            $order = SaleOrder::where('carrier_id', $user->carrier_id)
                              ->with('paymentTransaction.paymentType')
                              ->findOrFail($id);

            return response()->json([
                'status' => true,
                'data' => $order->paymentTransaction
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve payment history: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Complete delivery process with payment verification
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function completeDelivery(Request $request, $id): JsonResponse
    {
        try {
            $user = Auth::user();

            // Validate user is delivery personnel
            if (!$this->isDeliveryUser($user)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            // Find order with carrier validation
            $order = SaleOrder::where('carrier_id', $user->carrier_id)
                              ->findOrFail($id);

            // Check if payment is complete
            $balance = $order->grand_total - $order->paid_amount;
            $isPaymentComplete = $balance <= 0;

            // If payment is not complete, collect remaining payment
            if (!$isPaymentComplete) {
                // Validate payment data
                $paymentValidator = Validator::make($request->all(), [
                    'amount' => 'required|numeric|min:0|max:' . $balance,
                    'payment_type_id' => 'required|exists:payment_types,id',
                    'reference_number' => 'nullable|string|max:100',
                    'notes' => 'nullable|string|max:500'
                ]);

                if ($paymentValidator->fails()) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Payment validation failed',
                        'errors' => $paymentValidator->errors()
                    ], 422);
                }

                // Collect the payment
                $paymentData = [
                    'transaction_date' => now()->format('Y-m-d'),
                    'amount' => $request->amount,
                    'payment_type_id' => $request->payment_type_id,
                    'note' => $request->notes,
                    'reference_number' => $request->reference_number
                ];

                $payment = $this->paymentTransactionService->recordPayment($order, $paymentData);

                if (!$payment) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Failed to record payment'
                    ], 500);
                }

                // Update total paid amount
                $this->paymentTransactionService->updateTotalPaidAmountInModel($order);
            }

            // Now that payment is complete (or was already complete), update status to POD
            // Validate POD data
            $podValidator = Validator::make($request->all(), [
                'notes' => 'required|string|max:500', // Notes are mandatory for POD
                'signature' => 'nullable|string',
                'photos' => 'nullable|array',
                'photos.*' => 'string',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180'
            ]);

            if ($podValidator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'POD validation failed',
                    'errors' => $podValidator->errors()
                ], 422);
            }

            // Update status to POD using existing service
            $result = $this->saleOrderStatusService->updateSaleOrderStatus(
                $order,
                'POD',
                [
                    'notes' => $request->notes,
                    'signature' => $request->signature,
                    'proof_images' => $request->photos,
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude,
                    'changed_by' => $user->id
                ]
            );

            if ($result['success']) {
                return response()->json([
                    'status' => true,
                    'message' => 'Delivery completed successfully',
                    'data' => [
                        'order_id' => $order->id,
                        'order_code' => $order->order_code,
                        'status' => 'POD',
                        'payment_status' => 'complete'
                    ]
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => $result['message']
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to complete delivery: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if user is delivery personnel
     *
     * @param mixed $user
     * @return bool
     */
    private function isDeliveryUser($user): bool
    {
        return $user && $user->role && strtolower($user->role->name) === 'delivery';
    }
}
