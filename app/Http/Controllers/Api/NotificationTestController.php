<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use App\Services\FirebaseNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationTestController extends Controller
{
    protected $firebaseService;

    public function __construct(FirebaseNotificationService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    /**
     * إرسال إشعار تجريبي
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function sendTestNotification(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'token' => 'required|string',
            ]);

            $token = $request->token;
            $title = 'إشعار تجريبي';
            $body = 'هذا إشعار تجريبي من تطبيق دلتا';
            $data = [
                'type' => 'test',
                'time' => now()->toDateTimeString(),
            ];

            $result = $this->firebaseService->sendNotificationToDevice($token, $title, $body, $data);

            return response()->json([
                'status' => true,
                'message' => 'تم إرسال الإشعار التجريبي بنجاح',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'فشل في إرسال الإشعار: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * إرسال إشعار تجريبي لجميع المستخدمين النشطين
     *
     * @return JsonResponse
     */
    public function sendTestNotificationToAllUsers(): JsonResponse
    {
        try {
            $tokens = DeviceToken::where('is_active', true)
                ->pluck('token')
                ->toArray();

            if (empty($tokens)) {
                return response()->json([
                    'status' => false,
                    'message' => 'لا يوجد أجهزة نشطة لإرسال الإشعارات إليها'
                ], 404);
            }

            $title = 'إشعار تجريبي جماعي';
            $body = 'هذا إشعار تجريبي لجميع المستخدمين من تطبيق دلتا';
            $data = [
                'type' => 'test_all',
                'time' => now()->toDateTimeString(),
            ];

            $result = $this->firebaseService->sendNotificationToMultipleDevices($tokens, $title, $body, $data);

            return response()->json([
                'status' => true,
                'message' => 'تم إرسال الإشعار التجريبي لـ ' . count($tokens) . ' جهاز بنجاح',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'فشل في إرسال الإشعار: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Register a test device token
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function registerTestToken(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'token' => 'required|string',
                'user_id' => 'required|exists:users,id',
                'device_type' => 'required|in:android,ios,web',
            ]);

            $deviceToken = DeviceToken::updateOrCreate(
                [
                    'token' => $request->token,
                    'user_id' => $request->user_id
                ],
                [
                    'device_type' => $request->device_type,
                    'is_active' => true
                ]
            );

            return response()->json([
                'status' => true,
                'message' => 'تم تسجيل توكن الجهاز بنجاح',
                'data' => $deviceToken
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'فشل في تسجيل التوكن: ' . $e->getMessage()
            ], 500);
        }
    }
}