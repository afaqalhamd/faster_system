<?php

namespace App\Listeners;

use App\Events\OrderStatusUpdated;
use App\Services\FirebaseNotificationService;
use App\Models\Party\Party;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Exception;

class SendOrderStatusNotification implements ShouldQueue
{
    use InteractsWithQueue;

    private $firebaseNotificationService;

    /**
     * Create the event listener.
     *
     * @param FirebaseNotificationService $firebaseNotificationService
     */
    public function __construct(FirebaseNotificationService $firebaseNotificationService)
    {
        $this->firebaseNotificationService = $firebaseNotificationService;
    }

    /**
     * Handle the event.
     *
     * @param OrderStatusUpdated $event
     * @return void
     */
    public function handle(OrderStatusUpdated $event)
    {
        try {
            $order = $event->order;
            $oldStatus = $event->oldStatus;
            $newStatus = $event->newStatus;

            // التحقق من وجود party_id في الطلب
            if (!isset($order->party_id)) {
                Log::warning('Order has no party_id, skipping notification', [
                    'order_id' => $order->id ?? 'unknown'
                ]);
                return;
            }

            // جلب بيانات العميل
            $party = Party::find($order->party_id);

            if (!$party) {
                Log::warning('Party not found for order notification', [
                    'party_id' => $order->party_id,
                    'order_id' => $order->id ?? 'unknown'
                ]);
                return;
            }

            // التحقق من أن العميل نشط
            if (!$party->status) {
                Log::info('Party is inactive, skipping notification', [
                    'party_id' => $party->id,
                    'order_id' => $order->id ?? 'unknown'
                ]);
                return;
            }

            // التحقق من وجود fc_token
            if (empty($party->fc_token)) {
                Log::warning('Party has no FCM token, skipping notification', [
                    'party_id' => $party->id,
                    'order_id' => $order->id ?? 'unknown'
                ]);
                return;
            }

            // تحديد نوع الإشعار والرسالة
            $isNewOrder = empty($oldStatus);

            if ($isNewOrder) {
                // إشعار طلب جديد
                $title = 'طلب جديد';
                $body = sprintf(
                    'تم إنشاء طلبك رقم %s بنجاح',
                    $order->order_code ?? $order->id
                );
                $notificationType = 'order_created';
            } else {
                // إشعار تحديث حالة الطلب
                $statusLabels = [
                    'Pending' => 'قيد الانتظار',
                    'Confirmed' => 'مؤكد',
                    'Processing' => 'قيد المعالجة',
                    'Delivery' => 'قيد التوصيل',
                    'Delivered' => 'تم التوصيل',
                    'Cancelled' => 'ملغي',
                    'Returned' => 'مرتجع'
                ];

                $title = 'تحديث حالة الطلب';
                $body = sprintf(
                    'تم تحديث حالة طلبك رقم %s إلى: %s',
                    $order->order_code ?? $order->id,
                    $statusLabels[$newStatus] ?? $newStatus
                );
                $notificationType = 'order_status_update';
            }

            // إعداد البيانات
            $data = [
                'type' => $notificationType,
                'order_id' => (string)$order->id,
                'order_code' => $order->order_code ?? (string)$order->id,
                'order_status' => $newStatus,
                'old_status' => $oldStatus ?? '',
                'new_status' => $newStatus,
                'grand_total' => (string)($order->grand_total ?? 0),
                'priority' => 'high',
                'sound' => 'default',
                'timestamp' => now()->toISOString(),
            ];

            // إرسال الإشعار باستخدام FirebaseNotificationService
            $this->firebaseNotificationService->sendNotificationToDevice(
                $party->fc_token,
                $title,
                $body,
                $data
            );

            Log::info('Order notification sent to party', [
                'party_id' => $party->id,
                'party_name' => trim($party->first_name . ' ' . $party->last_name),
                'order_id' => $order->id ?? 'unknown',
                'order_code' => $order->order_code ?? 'unknown',
                'notification_type' => $notificationType,
                'old_status' => $oldStatus ?? 'new',
                'new_status' => $newStatus,
            ]);

        } catch (Exception $e) {
            Log::error('Failed to send order notification to party', [
                'order_id' => $event->order->id ?? 'unknown',
                'party_id' => $event->order->party_id ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // لا نرمي الاستثناء لتجنب فشل العملية الأساسية
            // الإشعار ليس حرجاً لنجاح الطلب
        }
    }

    /**
     * Handle a job failure.
     *
     * @param OrderStatusUpdated $event
     * @param \Throwable $exception
     * @return void
     */
    public function failed(OrderStatusUpdated $event, $exception)
    {
        Log::error('Order status notification listener failed permanently', [
            'order_id' => $event->order->id ?? 'unknown',
            'error' => $exception->getMessage()
        ]);
    }
}
