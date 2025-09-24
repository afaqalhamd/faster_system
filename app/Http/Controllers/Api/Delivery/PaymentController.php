<?php

namespace App\Http\Controllers\Api\Delivery;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Sale\SaleOrder;
use App\Services\PaymentTypeService;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    protected $paymentTypeService;

    public function __construct(PaymentTypeService $paymentTypeService)
    {
        $this->paymentTypeService = $paymentTypeService;
    }

    /**
     * Get payment details for an order
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        try {
            $user = Auth::user();

            // Validate user is delivery personnel
            if (!$user || !$this->isDeliveryUser($user)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            // Find order with carrier validation
            $order = SaleOrder::where('carrier_id', $user->carrier_id)
                              ->findOrFail($id);

            // Get available payment types
            $paymentTypes = $this->paymentTypeService->selectedPaymentTypesArray();

            return response()->json([
                'status' => true,
                'data' => [
                    'order' => [
                        'id' => $order->id,
                        'order_code' => $order->order_code,
                        'total_amount' => $order->grand_total,
                        'paid_amount' => $order->paid_amount,
                        'due_amount' => $order->grand_total - $order->paid_amount,
                        'payment_status' => $this->getPaymentStatus($order)
                    ],
                    'payment_methods' => $paymentTypes
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve payment details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payment status based on amounts
     *
     * @param SaleOrder $order
     * @return string
     */
    private function getPaymentStatus($order)
    {
        $balance = $order->grand_total - $order->paid_amount;

        if ($balance == 0) {
            return 'paid';
        } elseif ($order->paid_amount == 0) {
            return 'unpaid';
        } else {
            return 'partially_paid';
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
