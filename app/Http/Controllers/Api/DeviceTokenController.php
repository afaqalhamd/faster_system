<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DeviceTokenController extends Controller
{
    /**
     * تسجيل توكن جهاز جديد
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string',
            'device_type' => 'required|in:android,ios,web',
            'app_version' => 'nullable|string',
            'device_info' => 'nullable|array'
        ]);

        $user = auth()->user();

        // إلغاء تفعيل جميع الرموز السابقة لنفس المستخدم
        DeviceToken::where('user_id', $user->id)
            ->update(['is_active' => false]);

        // إنشاء أو تحديث الرمز الحالي
        $deviceToken = DeviceToken::updateOrCreate(
            [
                'user_id' => $user->id,
                'token' => $request->token
            ],
            [
                'device_type' => $request->device_type,
                'app_version' => $request->app_version,
                'device_info' => $request->device_info ? json_encode($request->device_info) : null,
                'is_active' => true,
                'last_used_at' => now()
            ]
        );

        // تحديث رمز FCM في جدول المستخدمين
        $user->update(['fc_token' => $request->token]);

        return response()->json([
            'status' => 'success',
            'message' => 'تم تسجيل رمز الجهاز بنجاح',
            'data' => [
                'device_token_id' => $deviceToken->id,
                'registered_at' => $deviceToken->updated_at
            ]
        ], 201);
    }

    /**
     * تحديث رمز الجهاز الحالي
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string',
            'device_type' => 'required|in:android,ios,web',
            'app_version' => 'nullable|string'
        ]);

        $user = auth()->user();

        // إلغاء تفعيل جميع الرموز السابقة
        DeviceToken::where('user_id', $user->id)
            ->update(['is_active' => false]);

        // تحديث أو إنشاء الرمز الجديد
        $deviceToken = DeviceToken::updateOrCreate(
            [
                'user_id' => $user->id,
                'token' => $request->token
            ],
            [
                'device_type' => $request->device_type,
                'app_version' => $request->app_version,
                'is_active' => true,
                'last_used_at' => now()
            ]
        );

        // تحديث رمز FCM في جدول المستخدمين
        $user->update(['fc_token' => $request->token]);

        return response()->json([
            'status' => 'success',
            'message' => 'تم تحديث رمز الجهاز بنجاح',
            'data' => [
                'device_token_id' => $deviceToken->id,
                'updated_at' => $deviceToken->updated_at
            ]
        ]);
    }

    /**
     * إلغاء تفعيل رمز الجهاز
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function deactivate(Request $request): JsonResponse
    {
        $user = auth()->user();

        // إلغاء تفعيل جميع رموز المستخدم
        $updatedCount = DeviceToken::where('user_id', $user->id)
            ->update(['is_active' => false]);

        // مسح رمز FCM من جدول المستخدمين
        $user->update(['fc_token' => null]);

        return response()->json([
            'status' => 'success',
            'message' => 'تم إلغاء تفعيل رموز الجهاز بنجاح',
            'data' => [
                'deactivated_tokens' => $updatedCount
            ]
        ]);
    }

    /**
     * التحقق من حالة تسجيل الرمز
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function status(Request $request): JsonResponse
    {
        $user = auth()->user();

        $activeTokens = DeviceToken::where('user_id', $user->id)
            ->where('is_active', true)
            ->get(['id', 'device_type', 'app_version', 'last_used_at', 'created_at']);

        return response()->json([
            'status' => 'success',
            'message' => 'حالة رموز الجهاز',
            'data' => [
                'user_id' => $user->id,
                'active_tokens_count' => $activeTokens->count(),
                'active_tokens' => $activeTokens,
                'fc_token_in_user_table' => $user->fc_token ? 'موجود' : 'غير موجود'
            ]
        ]);
    }
}
