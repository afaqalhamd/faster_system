<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sale\SaleOrder;
use App\Models\User;
use App\Models\Party\Party;
use App\Services\FirebaseNotificationService;
use Illuminate\Support\Facades\Log;

class ChatNotificationController extends Controller
{
    protected $firebaseNotificationService;

    public function __construct(FirebaseNotificationService $firebaseNotificationService)
    {
        $this->firebaseNotificationService = $firebaseNotificationService;
    }

    /**
     * إرسال إشعار رسالة شات
     */
    public function sendChatNotification(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|integer',
            'sender_id' => 'nullable|integer', // اختياري - لحفظ في Firebase
            'sender_name' => 'required|string',
            'sender_type' => 'required|in:driver,customer',
            'message' => 'required|string',
            'message_type' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        try {
            // تحديد نوع المستلم (عكس المرسل)
            $recipientType = $validated['sender_type'] === 'driver' ? 'customer' : 'driver';

            Log::info('Chat notification request', [
                'order_id' => $validated['order_id'],
                'sender_type' => $validated['sender_type'],
                'recipient_type' => $recipientType,
            ]);

            // الحصول على معلومات الطلب
            $order = SaleOrder::find($validated['order_id']);
            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found',
                ], 404);
            }

            // إذا كان المستلم هو driver، أرسل لجميع المستخدمين المرتبطين بشركة الناقل
            if ($recipientType === 'driver') {
                return $this->sendChatNotificationToCarrierUsers($order, $validated, $recipientType);
            }

            // إذا كان المستلم هو customer، أرسل للعميل فقط
            $fcmToken = $this->getRecipientFCMToken(
                $validated['order_id'],
                $recipientType
            );

            if (!$fcmToken) {
                Log::error('FCM token not found', [
                    'order_id' => $validated['order_id'],
                    'recipient_type' => $recipientType,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'FCM token not found for recipient',
                    'debug' => [
                        'order_id' => $validated['order_id'],
                        'recipient_type' => $recipientType,
                    ],
                ], 404);
            }

            Log::info('FCM token found', [
                'order_id' => $validated['order_id'],
                'recipient_type' => $recipientType,
                'token_length' => strlen($fcmToken),
            ]);

            // إعداد الإشعار
            $messageType = $validated['message_type'] ?? 'text';
            $notificationTitle = $messageType === 'location'
                ? 'موقع جديد'
                : 'رسالة جديدة';

            $notificationBody = $messageType === 'location'
                ? "{$validated['sender_name']} شارك موقعه الجغرافي"
                : "{$validated['sender_name']}: {$validated['message']}";

            // إعداد البيانات
            $data = [
                'type' => 'chat_message',
                'order_id' => (string)$validated['order_id'],
                'order_code' => $order->order_code ?? '',
                'sender_name' => $validated['sender_name'],
                'sender_type' => $validated['sender_type'],
                'msg_type' => $messageType,
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                'priority' => 'high',
                'sound' => 'default',
                'timestamp' => now()->toISOString(),
            ];

            // إضافة sender_id إذا كان موجوداً
            if (isset($validated['sender_id'])) {
                $data['sender_id'] = (string)$validated['sender_id'];
            }

            if ($messageType === 'location') {
                $data['latitude'] = (string)$validated['latitude'];
                $data['longitude'] = (string)$validated['longitude'];
            }

            // إرسال الإشعار باستخدام FirebaseNotificationService
            $this->firebaseNotificationService->sendNotificationToDevice(
                $fcmToken,
                $notificationTitle,
                $notificationBody,
                $data
            );

            Log::info('Chat notification sent successfully', [
                'order_id' => $validated['order_id'],
                'recipient_type' => $recipientType,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إرسال الإشعار بنجاح',
                'data' => [
                    'notification_sent' => true,
                    'recipient_type' => $recipientType,
                    'fcm_token_found' => true,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Chat notification error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'فشل إرسال الإشعار',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * إرسال إشعار الشات لجميع المستخدمين المرتبطين بشركة الناقل
     */
    private function sendChatNotificationToCarrierUsers($order, $validated, $recipientType)
    {
        try {
            // التحقق من وجود carrier_id
            if (!$order->carrier_id) {
                Log::error('Carrier not assigned for order', [
                    'order_id' => $order->id,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Carrier not assigned to this order',
                ], 404);
            }

            // الحصول على جميع المستخدمين المرتبطين بشركة الناقل
            $deliveryUsers = User::where('carrier_id', $order->carrier_id)
                ->whereHas('role', function ($query) {
                    $query->where('name', 'Delivery');
                })
                ->whereNotNull('fc_token')
                ->where('fc_token', '!=', '')
                ->get();

            if ($deliveryUsers->isEmpty()) {
                Log::warning('No delivery users found for carrier', [
                    'order_id' => $order->id,
                    'carrier_id' => $order->carrier_id,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'No delivery users found for this carrier',
                ], 404);
            }

            Log::info('Found delivery users for carrier', [
                'order_id' => $order->id,
                'carrier_id' => $order->carrier_id,
                'users_count' => $deliveryUsers->count(),
            ]);

            // إعداد الإشعار
            $messageType = $validated['message_type'] ?? 'text';
            $notificationTitle = $messageType === 'location'
                ? 'موقع جديد'
                : 'رسالة جديدة';

            $notificationBody = $messageType === 'location'
                ? "{$validated['sender_name']} شارك موقعه الجغرافي"
                : "{$validated['sender_name']}: {$validated['message']}";

            // إعداد البيانات
            $data = [
                'type' => 'chat_message',
                'order_id' => (string)$validated['order_id'],
                'order_code' => $order->order_code ?? '',
                'sender_name' => $validated['sender_name'],
                'sender_type' => $validated['sender_type'],
                'msg_type' => $messageType,
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                'priority' => 'high',
                'sound' => 'default',
                'timestamp' => now()->toISOString(),
            ];

            // إضافة sender_id إذا كان موجوداً
            if (isset($validated['sender_id'])) {
                $data['sender_id'] = (string)$validated['sender_id'];
            }

            if ($messageType === 'location') {
                $data['latitude'] = (string)$validated['latitude'];
                $data['longitude'] = (string)$validated['longitude'];
            }

            // إرسال الإشعار لجميع المستخدمين
            $notificationsSent = 0;
            $errors = [];

            foreach ($deliveryUsers as $user) {
                try {
                    $this->firebaseNotificationService->sendNotificationToDevice(
                        $user->fc_token,
                        $notificationTitle,
                        $notificationBody,
                        $data
                    );

                    $notificationsSent++;

                    Log::info('Chat notification sent to delivery user', [
                        'user_id' => $user->id,
                        'user_name' => $user->username,
                        'order_id' => $order->id,
                    ]);

                } catch (\Exception $e) {
                    $error = "Failed to send notification to user {$user->id}: " . $e->getMessage();
                    $errors[] = $error;

                    Log::error('Failed to send chat notification to user', [
                        'user_id' => $user->id,
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // إعداد الاستجابة
            $response = [
                'success' => true,
                'message' => sprintf(
                    'Sent %d chat notifications to delivery users',
                    $notificationsSent
                ),
                'data' => [
                    'notification_sent' => true,
                    'recipient_type' => $recipientType,
                    'fcm_token_found' => true,
                    'notifications_sent' => $notificationsSent,
                    'total_users' => $deliveryUsers->count(),
                ],
            ];

            if (!empty($errors)) {
                $response['data']['errors'] = $errors;
                $response['message'] .= sprintf(' (%d failures)', count($errors));
            }

            Log::info('Chat notification process completed', [
                'order_id' => $order->id,
                'carrier_id' => $order->carrier_id,
                'notifications_sent' => $notificationsSent,
                'total_users' => $deliveryUsers->count(),
                'errors_count' => count($errors),
            ]);

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('Chat notification to carrier users error', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'فشل إرسال الإشعار',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * الحصول على FCM Token للمستلم
     */
    private function getRecipientFCMToken($orderId, $recipientType)
    {
        try {
            // الحصول على معلومات الطلب
            $order = SaleOrder::with(['party', 'user'])->find($orderId);
            if (!$order) {
                Log::error("Order not found: {$orderId}");
                return null;
            }

            Log::info("Order found", [
                'order_id' => $orderId,
                'party_id' => $order->party_id,
                'carrier_id' => $order->carrier_id,
                'recipient_type' => $recipientType,
            ]);

            // تحديد المستلم بناءً على النوع
            if ($recipientType === 'customer') {
                // العميل موجود في جدول Party
                if (!$order->party) {
                    Log::error("Party not found for order: {$orderId}, party_id: {$order->party_id}");
                    return null;
                }

                Log::info("Party found", [
                    'party_id' => $order->party->id,
                    'has_fc_token' => !empty($order->party->fc_token),
                ]);

                if (!$order->party->fc_token) {
                    Log::error("FC token not found for party: {$order->party_id}");
                    return null;
                }

                return $order->party->fc_token;
            } else {
                // السائق موجود في جدول Users (carrier_id)
                if (!$order->carrier_id) {
                    Log::error("Carrier not assigned for order: {$orderId}");
                    return null;
                }

                $driver = User::find($order->carrier_id);
                if (!$driver) {
                    Log::error("Driver not found: {$order->carrier_id}");
                    return null;
                }

                Log::info("Driver found", [
                    'driver_id' => $driver->id,
                    'driver_username' => $driver->username,
                    'has_fc_token' => !empty($driver->fc_token),
                    'fc_token_length' => $driver->fc_token ? strlen($driver->fc_token) : 0,
                ]);

                if (!$driver->fc_token) {
                    Log::error("FC token not found for driver: {$driver->id}");
                    return null;
                }

                Log::info("Returning driver FC token", [
                    'driver_id' => $driver->id,
                    'token_preview' => substr($driver->fc_token, 0, 20) . '...',
                ]);

                return $driver->fc_token;
            }

        } catch (\Exception $e) {
            Log::error('Error getting FCM token: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }
}
