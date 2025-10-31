<?php

namespace App\Http\Controllers\Api\Test;

use App\Http\Controllers\Controller;
use App\Models\Sale\SaleOrder;
use App\Models\User;
use App\Models\Carrier;
use App\Services\CarrierNotificationService;
use App\Services\SaleOrderStatusService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class NotificationTestController extends Controller
{
    private $carrierNotificationService;
    private $saleOrderStatusService;

    public function __construct(
        CarrierNotificationService $carrierNotificationService,
        SaleOrderStatusService $saleOrderStatusService
    ) {
        $this->carrierNotificationService = $carrierNotificationService;
        $this->saleOrderStatusService = $saleOrderStatusService;
    }

    /**
     * Test carrier notification system
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function testCarrierNotification(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'carrier_id' => 'required|integer|exists:carriers,id',
                'message' => 'nullable|string|max:255'
            ]);

            $carrierId = $request->carrier_id;
            $message = $request->message;

            $result = $this->carrierNotificationService->testCarrierNotification($carrierId, $message);

            return response()->json([
                'status' => 'success',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Test failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test delivery notification by changing sale order status to delivery
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function testDeliveryNotification(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'sale_order_id' => 'required|integer|exists:sale_orders,id'
            ]);

            $saleOrder = SaleOrder::with(['carrier', 'party'])->findOrFail($request->sale_order_id);

            if (!$saleOrder->carrier_id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Sale order does not have a carrier assigned'
                ], 400);
            }

            // Store the current status to restore later
            $originalStatus = $saleOrder->order_status;

            // Update status to Delivery to trigger notification
            $result = $this->saleOrderStatusService->updateSaleOrderStatus(
                $saleOrder,
                'Delivery',
                [
                    'notes' => 'Test notification - status changed for testing purposes'
                ]
            );

            if (!$result['success']) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to update sale order status: ' . $result['message']
                ], 500);
            }

            // Restore original status
            $saleOrder->update(['order_status' => $originalStatus]);

            return response()->json([
                'status' => 'success',
                'message' => 'Delivery notification test completed',
                'data' => [
                    'sale_order_id' => $saleOrder->id,
                    'order_code' => $saleOrder->order_code,
                    'carrier_id' => $saleOrder->carrier_id,
                    'carrier_name' => $saleOrder->carrier->name ?? 'Unknown',
                    'original_status' => $originalStatus,
                    'test_status' => 'Delivery',
                    'status_restored' => true,
                    'notification_result' => $result['notification_result'] ?? null
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Test failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get delivery users for a specific carrier
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getDeliveryUsers(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'carrier_id' => 'required|integer|exists:carriers,id'
            ]);

            $carrierId = $request->carrier_id;
            $carrier = Carrier::find($carrierId);

            $deliveryUsers = $this->carrierNotificationService->getDeliveryUsersForCarrier($carrierId);

            $usersData = $deliveryUsers->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => trim($user->first_name . ' ' . $user->last_name),
                    'email' => $user->email,
                    'has_fc_token' => !empty($user->fc_token),
                    'fc_token_length' => $user->fc_token ? strlen($user->fc_token) : 0,
                    'created_at' => $user->created_at
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => [
                    'carrier' => [
                        'id' => $carrier->id,
                        'name' => $carrier->name,
                        'email' => $carrier->email
                    ],
                    'delivery_users_count' => $deliveryUsers->count(),
                    'delivery_users' => $usersData
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get delivery users: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get list of carriers with delivery user counts
     *
     * @return JsonResponse
     */
    public function getCarriersWithDeliveryUsers(): JsonResponse
    {
        try {
            $carriers = Carrier::with(['users' => function ($query) {
                $query->whereHas('role', function ($roleQuery) {
                    $roleQuery->where('name', 'delivery');
                })->whereNotNull('fc_token')->where('fc_token', '!=', '');
            }])->get();

            $carriersData = $carriers->map(function ($carrier) {
                return [
                    'id' => $carrier->id,
                    'name' => $carrier->name,
                    'email' => $carrier->email,
                    'delivery_users_count' => $carrier->users->count(),
                    'delivery_users' => $carrier->users->map(function ($user) {
                        return [
                            'id' => $user->id,
                            'name' => trim($user->first_name . ' ' . $user->last_name),
                            'email' => $user->email,
                            'has_fc_token' => !empty($user->fc_token)
                        ];
                    })
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => $carriersData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get carriers: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get sale orders that can be used for testing
     *
     * @return JsonResponse
     */
    public function getTestSaleOrders(): JsonResponse
    {
        try {
            $saleOrders = SaleOrder::with(['carrier', 'party'])
                ->whereNotNull('carrier_id')
                ->whereNotIn('order_status', ['Cancelled', 'Returned'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            $ordersData = $saleOrders->map(function ($order) {
                return [
                    'id' => $order->id,
                    'order_code' => $order->order_code,
                    'order_status' => $order->order_status,
                    'carrier_id' => $order->carrier_id,
                    'carrier_name' => $order->carrier->name ?? 'Unknown',
                    'customer_name' => $order->party ?
                        trim($order->party->first_name . ' ' . $order->party->last_name) :
                        'Unknown Customer',
                    'grand_total' => $order->grand_total,
                    'created_at' => $order->created_at
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => $ordersData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get test sale orders: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Simulate sale order status change to delivery (for testing)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function simulateStatusChange(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'sale_order_id' => 'required|integer|exists:sale_orders,id',
                'new_status' => 'required|string|in:Pending,Processing,Completed,Delivery,POD,Cancelled,Returned',
                'notes' => 'nullable|string|max:500'
            ]);

            $saleOrder = SaleOrder::with(['carrier', 'party'])->findOrFail($request->sale_order_id);
            $newStatus = $request->new_status;
            $notes = $request->notes ?? "Test status change to {$newStatus}";

            if (!$saleOrder->carrier_id && $newStatus === 'Delivery') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Sale order must have a carrier assigned to test delivery notifications'
                ], 400);
            }

            $result = $this->saleOrderStatusService->updateSaleOrderStatus(
                $saleOrder,
                $newStatus,
                ['notes' => $notes]
            );

            if (!$result['success']) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to update status: ' . $result['message']
                ], 500);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Status updated successfully',
                'data' => [
                    'sale_order_id' => $saleOrder->id,
                    'order_code' => $saleOrder->order_code,
                    'new_status' => $newStatus,
                    'carrier_id' => $saleOrder->carrier_id,
                    'carrier_name' => $saleOrder->carrier->name ?? 'Unknown',
                    'update_result' => $result
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Simulation failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
