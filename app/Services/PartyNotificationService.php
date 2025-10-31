<?php

namespace App\Services;

use App\Models\Party\Party;
use App\Models\Sale\SaleOrder;
use App\Services\FirebaseNotificationService;
use Illuminate\Support\Facades\Log;
use Exception;

class PartyNotificationService
{
    private $firebaseNotificationService;

    public function __construct(FirebaseNotificationService $firebaseNotificationService)
    {
        $this->firebaseNotificationService = $firebaseNotificationService;
    }

    /**
     * إرسال إشعار للعميل عند إنشاء طلب جديد
     *
     * @param SaleOrder $saleOrder
     * @return array
     */
    public function sendNewOrderNotification(SaleOrder $saleOrder): array
    {
        try {
            // التحقق من وجود العميل
            if (!$saleOrder->party) {
                return [
                    'success' => true,
                    'message' => 'No party associated with this order',
                    'notifications_sent' => 0
                ];
            }

            $party = $saleOrder->party;

            // التحقق من وجود FCM token
            if (!$party->fc_token) {
                Log::warning('No FCM token found for party', [
                    'party_id' => $party->id,
                    'sale_order_id' => $saleOrder->id
                ]);

                return [
                    'success' => true,
                    'message' => 'Party does not have FCM token',
                    'notifications_sent' => 0
                ];
            }

            // إعداد محتوى الإشعار
            $title = '🎉 طلب جديد';
            $body = sprintf(
                'تم إنشاء طلبك رقم %s بنجاح',
                $saleOrder->order_code
            );

            // البيانات الإضافية للإشعار
            $notificationData = [
                'order_id' => (string) $saleOrder->id,
                'order_code' => $saleOrder->order_code,
                'type' => 'new_order',
                'grand_total' => (string) $saleOrder->grand_total,
                'order_date' => $saleOrder->order_date,
                'notification_type' => 'order_created',
                'priority' => 'high',
                'sound' => 'default',
                'timestamp' => now()->toISOString()
            ];

            // إرسال الإشعار عبر Firebase
            $this->firebaseNotificationService->sendNotificationToDevice(
                $party->fc_token,
                $title,
                $body,
                $notificationData
            );

            Log::info('New order notification sent to party', [
                'party_id' => $party->id,
                'party_name' => trim($party->first_name . ' ' . $party->last_name),
                'sale_order_id' => $saleOrder->id,
                'order_code' => $saleOrder->order_code
            ]);

            return [
                'success' => true,
                'message' => 'New order notification sent successfully',
                'notifications_sent' => 1
            ];

        } catch (Exception $e) {
            Log::error('Failed to send new order notification to party', [
                'sale_order_id' => $saleOrder->id,
                'party_id' => $saleOrder->party_id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send notification: ' . $e->getMessage(),
                'notifications_sent' => 0
            ];
        }
    }

    /**
     * إرسال إشعار تحديث حالة الطلب
     *
     * @param SaleOrder $saleOrder
     * @param string $newStatus
     * @param string $previousStatus
     * @return array
     */
    public function sendOrderStatusNotification(SaleOrder $saleOrder, string $newStatus, string $previousStatus): array
    {
        try {
            // التحقق من وجود العميل
            if (!$saleOrder->party) {
                return [
                    'success' => true,
                    'message' => 'No party associated with this order',
                    'notifications_sent' => 0
                ];
            }

            $party = $saleOrder->party;

            // التحقق من وجود FCM token
            if (!$party->fc_token) {
                Log::warning('No FCM token found for party', [
                    'party_id' => $party->id,
                    'sale_order_id' => $saleOrder->id
                ]);

                return [
                    'success' => true,
                    'message' => 'Party does not have FCM token',
                    'notifications_sent' => 0
                ];
            }

            $statusLabels = [
                'Pending' => 'قيد الانتظار',
                'Confirmed' => 'مؤكد',
                'Processing' => 'قيد المعالجة',
                'Delivery' => 'قيد التوصيل',
                'POD' => 'تم التوصيل',
                'Cancelled' => 'ملغي',
                'Returned' => 'مرتجع'
            ];

            $title = '📦 تحديث حالة الطلب';
            $body = sprintf(
                'تم تحديث حالة طلبك رقم %s إلى: %s',
                $saleOrder->order_code,
                $statusLabels[$newStatus] ?? $newStatus
            );

            $notificationData = [
                'order_id' => (string) $saleOrder->id,
                'order_code' => $saleOrder->order_code,
                'type' => 'status_update',
                'status' => $newStatus,
                'previous_status' => $previousStatus,
                'status_label' => $statusLabels[$newStatus] ?? $newStatus,
                'notification_type' => 'status_update',
                'priority' => 'default',
                'sound' => 'default',
                'timestamp' => now()->toISOString()
            ];

            $this->firebaseNotificationService->sendNotificationToDevice(
                $party->fc_token,
                $title,
                $body,
                $notificationData
            );

            Log::info('Order status notification sent to party', [
                'party_id' => $party->id,
                'sale_order_id' => $saleOrder->id,
                'new_status' => $newStatus,
                'previous_status' => $previousStatus
            ]);

            return [
                'success' => true,
                'message' => 'Order status notification sent successfully',
                'notifications_sent' => 1
            ];

        } catch (Exception $e) {
            Log::error('Failed to send order status notification to party', [
                'sale_order_id' => $saleOrder->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send notification: ' . $e->getMessage(),
                'notifications_sent' => 0
            ];
        }
    }

    /**
     * إرسال إشعار توصيل الطلب
     *
     * @param SaleOrder $saleOrder
     * @return array
     */
    public function sendOrderDeliveredNotification(SaleOrder $saleOrder): array
    {
        try {
            if (!$saleOrder->party || !$saleOrder->party->fc_token) {
                return [
                    'success' => true,
                    'message' => 'No party or FCM token',
                    'notifications_sent' => 0
                ];
            }

            $party = $saleOrder->party;

            $title = '✅ تم توصيل الطلب';
            $body = sprintf(
                'تم توصيل طلبك رقم %s بنجاح',
                $saleOrder->order_code
            );

            $notificationData = [
                'order_id' => (string) $saleOrder->id,
                'order_code' => $saleOrder->order_code,
                'type' => 'order_delivered',
                'notification_type' => 'delivery_confirmation',
                'priority' => 'high',
                'sound' => 'default',
                'timestamp' => now()->toISOString()
            ];

            $this->firebaseNotificationService->sendNotificationToDevice(
                $party->fc_token,
                $title,
                $body,
                $notificationData
            );

            Log::info('Order delivered notification sent to party', [
                'party_id' => $party->id,
                'sale_order_id' => $saleOrder->id
            ]);

            return [
                'success' => true,
                'message' => 'Order delivered notification sent successfully',
                'notifications_sent' => 1
            ];

        } catch (Exception $e) {
            Log::error('Failed to send order delivered notification', [
                'sale_order_id' => $saleOrder->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send notification: ' . $e->getMessage(),
                'notifications_sent' => 0
            ];
        }
    }

    /**
     * إرسال إشعار استلام دفعة
     *
     * @param Party $party
     * @param float $amount
     * @param string $reference
     * @return array
     */
    public function sendPaymentReceivedNotification(Party $party, float $amount, string $reference = ''): array
    {
        try {
            if (!$party->fc_token) {
                return [
                    'success' => true,
                    'message' => 'Party does not have FCM token',
                    'notifications_sent' => 0
                ];
            }

            $title = '💰 تم استلام الدفعة';
            $body = sprintf(
                'تم استلام دفعتك بمبلغ %s',
                number_format($amount, 2)
            );

            $notificationData = [
                'amount' => (string) $amount,
                'reference' => $reference,
                'type' => 'payment_received',
                'notification_type' => 'payment',
                'priority' => 'default',
                'sound' => 'default',
                'timestamp' => now()->toISOString()
            ];

            $this->firebaseNotificationService->sendNotificationToDevice(
                $party->fc_token,
                $title,
                $body,
                $notificationData
            );

            Log::info('Payment received notification sent to party', [
                'party_id' => $party->id,
                'amount' => $amount
            ]);

            return [
                'success' => true,
                'message' => 'Payment notification sent successfully',
                'notifications_sent' => 1
            ];

        } catch (Exception $e) {
            Log::error('Failed to send payment notification', [
                'party_id' => $party->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send notification: ' . $e->getMessage(),
                'notifications_sent' => 0
            ];
        }
    }

    /**
     * إرسال إشعار مخصص
     *
     * @param Party $party
     * @param string $title
     * @param string $body
     * @param array $data
     * @return array
     */
    public function sendCustomNotification(Party $party, string $title, string $body, array $data = []): array
    {
        try {
            if (!$party->fc_token) {
                return [
                    'success' => true,
                    'message' => 'Party does not have FCM token',
                    'notifications_sent' => 0
                ];
            }

            $notificationData = array_merge($data, [
                'type' => 'custom',
                'notification_type' => 'general',
                'priority' => 'default',
                'sound' => 'default',
                'timestamp' => now()->toISOString()
            ]);

            $this->firebaseNotificationService->sendNotificationToDevice(
                $party->fc_token,
                $title,
                $body,
                $notificationData
            );

            Log::info('Custom notification sent to party', [
                'party_id' => $party->id,
                'title' => $title
            ]);

            return [
                'success' => true,
                'message' => 'Custom notification sent successfully',
                'notifications_sent' => 1
            ];

        } catch (Exception $e) {
            Log::error('Failed to send custom notification', [
                'party_id' => $party->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send notification: ' . $e->getMessage(),
                'notifications_sent' => 0
            ];
        }
    }
}
