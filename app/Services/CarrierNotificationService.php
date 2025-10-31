<?php

namespace App\Services;

use App\Models\Sale\SaleOrder;
use App\Models\User;
use App\Services\FirebaseNotificationService;
use Illuminate\Support\Facades\Log;
use Exception;

class CarrierNotificationService
{
    private $firebaseNotificationService;

    public function __construct(FirebaseNotificationService $firebaseNotificationService)
    {
        $this->firebaseNotificationService = $firebaseNotificationService;
    }

    /**
     * Send notification to delivery users when sale order status changes to delivery
     *
     * @param SaleOrder $saleOrder
     * @param string $newStatus
     * @param string $previousStatus
     * @return array
     */
    public function sendDeliveryNotification(SaleOrder $saleOrder, string $newStatus, string $previousStatus): array
    {
        try {
            // Only send notifications when status changes to 'Delivery'
            if ($newStatus !== 'Delivery') {
                return [
                    'success' => true,
                    'message' => 'No notification needed - status is not Delivery',
                    'notifications_sent' => 0
                ];
            }

            // Find delivery users for this carrier
            $deliveryUsers = User::where('carrier_id', $saleOrder->carrier_id)
                ->whereHas('role', function ($query) {
                    $query->where('name', 'delivery');
                })
                ->whereNotNull('fc_token')
                ->where('fc_token', '!=', '')
                ->get();

            if ($deliveryUsers->isEmpty()) {
                Log::warning('No delivery users found for carrier notification', [
                    'sale_order_id' => $saleOrder->id,
                    'carrier_id' => $saleOrder->carrier_id,
                    'new_status' => $newStatus
                ]);

                return [
                    'success' => true,
                    'message' => 'No delivery users found for this carrier',
                    'notifications_sent' => 0
                ];
            }

            // Prepare notification content
            $title = '📦طلب جديد للتسليم';
$body = sprintf('الطلب %s جاهز للاستلام',
 $saleOrder->order_code);
;

            // Additional data for the notification
            $notificationData = [
                'order_id' => (string) $saleOrder->id,
                'order_code' => $saleOrder->order_code,
                'type' => 'new_order', // ✅ Added for Flutter compatibility
                'status' => $newStatus,
                'previous_status' => $previousStatus,
                'party_name' => $saleOrder->party ?
                    trim($saleOrder->party->first_name . ' ' . $saleOrder->party->last_name) :
                    'Unknown Customer',
                'grand_total' => (string) $saleOrder->grand_total,
                'notification_type' => 'delivery_assignment',
                'priority' => 'high', // ✅ Added notification priority
                'sound' => 'default', // ✅ Added notification sound
                'timestamp' => now()->toISOString()
            ];

            $notificationsSent = 0;
            $errors = [];

            // Send notification to each delivery user
            foreach ($deliveryUsers as $user) {
                try {
                    $this->firebaseNotificationService->sendNotificationToDevice(
                        $user->fc_token,
                        $title,
                        $body,
                        $notificationData
                    );

                    $notificationsSent++;

                    Log::info('Delivery notification sent successfully', [
                        'user_id' => $user->id,
                        'user_name' => trim($user->first_name . ' ' . $user->last_name),
                        'sale_order_id' => $saleOrder->id,
                        'order_code' => $saleOrder->order_code
                    ]);

                } catch (Exception $e) {
                    $error = "Failed to send notification to user {$user->id}: " . $e->getMessage();
                    $errors[] = $error;

                    Log::error('Failed to send delivery notification', [
                        'user_id' => $user->id,
                        'sale_order_id' => $saleOrder->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Prepare response
            $response = [
                'success' => true,
                'message' => sprintf(
                    'Sent %d notifications to delivery users for carrier %s',
                    $notificationsSent,
                    $saleOrder->carrier->name ?? $saleOrder->carrier_id
                ),
                'notifications_sent' => $notificationsSent,
                'total_users' => $deliveryUsers->count()
            ];

            if (!empty($errors)) {
                $response['errors'] = $errors;
                $response['message'] .= sprintf(' (%d failures)', count($errors));
            }

            Log::info('Delivery notification process completed', [
                'sale_order_id' => $saleOrder->id,
                'carrier_id' => $saleOrder->carrier_id,
                'notifications_sent' => $notificationsSent,
                'total_users' => $deliveryUsers->count(),
                'errors_count' => count($errors)
            ]);

            return $response;

        } catch (Exception $e) {
            Log::error('Carrier notification service error', [
                'sale_order_id' => $saleOrder->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send carrier notifications: ' . $e->getMessage(),
                'notifications_sent' => 0
            ];
        }
    }

    /**
     * Send notification for order status updates to specific delivery user
     *
     * @param SaleOrder $saleOrder
     * @param User $deliveryUser
     * @param string $newStatus
     * @param string $message
     * @return array
     */
    public function sendOrderUpdateNotification(SaleOrder $saleOrder, User $deliveryUser, string $newStatus, string $message = null): array
    {
        try {
            if (!$deliveryUser->fc_token) {
                return [
                    'success' => false,
                    'message' => 'User does not have FCM token'
                ];
            }

            $title = 'تحديث حالة الطلب - Order Status Update';
            $body = $message ?? sprintf(
                'تم تحديث طلب %s إلى %s - Order %s updated to %s',
                $saleOrder->order_code,
                $newStatus,
                $saleOrder->order_code,
                $newStatus
            );

            $notificationData = [
                'order_id' => (string) $saleOrder->id,
                'order_code' => $saleOrder->order_code,
                'type' => 'order_update', // ✅ Added for Flutter compatibility
                'status' => $newStatus,
                'notification_type' => 'status_update',
                'priority' => 'default', // ✅ Added notification priority
                'sound' => 'default', // ✅ Added notification sound
                'timestamp' => now()->toISOString()
            ];

            $this->firebaseNotificationService->sendNotificationToDevice(
                $deliveryUser->fc_token,
                $title,
                $body,
                $notificationData
            );

            Log::info('Order update notification sent', [
                'user_id' => $deliveryUser->id,
                'sale_order_id' => $saleOrder->id,
                'new_status' => $newStatus
            ]);

            return [
                'success' => true,
                'message' => 'Order update notification sent successfully'
            ];

        } catch (Exception $e) {
            Log::error('Failed to send order update notification', [
                'user_id' => $deliveryUser->id,
                'sale_order_id' => $saleOrder->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send notification: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get delivery users for a specific carrier
     *
     * @param int $carrierId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getDeliveryUsersForCarrier(int $carrierId)
    {
        return User::where('carrier_id', $carrierId)
            ->whereHas('role', function ($query) {
                $query->where('name', 'delivery');
            })
            ->whereNotNull('fc_token')
            ->where('fc_token', '!=', '')
            ->get();
    }

    /**
     * Test notification system
     *
     * @param int $carrierId
     * @param string $testMessage
     * @return array
     */
    public function testCarrierNotification(int $carrierId, string $testMessage = null): array
    {
        try {
            $deliveryUsers = $this->getDeliveryUsersForCarrier($carrierId);

            if ($deliveryUsers->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'No delivery users found for carrier ' . $carrierId,
                    'notifications_sent' => 0
                ];
            }

            $title = 'اختبار الإشعارات - Notification Test';
            $body = $testMessage ?? 'هذا اختبار للإشعارات - This is a notification test';

            $notificationData = [
                'type' => 'system', // ✅ Added for Flutter compatibility
                'notification_type' => 'test',
                'carrier_id' => (string) $carrierId,
                'priority' => 'default', // ✅ Added notification priority
                'sound' => 'default', // ✅ Added notification sound
                'timestamp' => now()->toISOString()
            ];

            $notificationsSent = 0;
            $errors = [];

            foreach ($deliveryUsers as $user) {
                try {
                    $this->firebaseNotificationService->sendNotificationToDevice(
                        $user->fc_token,
                        $title,
                        $body,
                        $notificationData
                    );
                    $notificationsSent++;
                } catch (Exception $e) {
                    $errors[] = "User {$user->id}: " . $e->getMessage();
                }
            }

            return [
                'success' => true,
                'message' => "Test notifications sent to {$notificationsSent} users",
                'notifications_sent' => $notificationsSent,
                'total_users' => $deliveryUsers->count(),
                'errors' => $errors
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Test failed: ' . $e->getMessage(),
                'notifications_sent' => 0
            ];
        }
    }
}
