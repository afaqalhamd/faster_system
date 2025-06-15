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
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string',
            'device_type' => 'required|in:android,ios,web',
        ]);

        $user = auth()->user();

        // تحقق مما إذا كان التوكن موجودًا بالفعل
        $deviceToken = DeviceToken::where('token', $request->token)
            ->where('user_id', $user->id)
            ->first();

        if (!$deviceToken) {
            // إنشاء توكن جديد إذا لم يكن موجودًا
            $deviceToken = DeviceToken::create([
                'user_id' => $user->id,
                'token' => $request->token,
                'device_type' => $request->device_type,
                'is_active' => true
            ]);
        } else {
            // تحديث التوكن الموجود
            $deviceToken->update([
                'device_type' => $request->device_type,
                'is_active' => true
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'تم تسجيل توكن الجهاز بنجاح',
            'data' => $deviceToken
        ]);
    }

    /**
     * إلغاء تنشيط توكن جهاز
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function destroy(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $user = auth()->user();

        $deviceToken = DeviceToken::where('token', $request->token)
            ->where('user_id', $user->id)
            ->first();

        if ($deviceToken) {
            $deviceToken->update(['is_active' => false]);
        }

        return response()->json([
            'status' => true,
            'message' => 'تم إلغاء تنشيط توكن الجهاز بنجاح'
        ]);
    }
}