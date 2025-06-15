<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class AuthController extends Controller
{
    /**
     * Handle user login request
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'fc_token' => 'nullable|string' // تحقق من صحة التوكن
        ]);

        try {
            if (Auth::attempt($request->only('email', 'password'))) {
                $user = Auth::user();
                $token = $user->createToken('api-token')->plainTextToken;

                // حفظ/تحديث FCM Token إذا تم إرساله
                if ($request->fc_token && $request->fc_token != $user->fc_token) {
                    $user->update(['fc_token' => $request->fc_token]);
                    Log::info("FCM Token updated for user: {$user->id}");
                }

                return response()->json([
                    'status' => 'success',
                    'message' => 'Login successful',
                    'data' => [
                        'user' => $user->load(['roles', 'userWarehouses']), // تحميل العلاقات
                        'token' => $token,
                        'avatar_url' => $user->avatar ? url('/api/getimage/' . $user->avatar) : null
                    ]
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Invalid credentials',
            ], 401);

        } catch (\Exception $e) {
            Log::error("Login error: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred during login'
            ], 500);
        }
    }

    /**
     * Handle user logout
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        try {
            $user = $request->user();

            // مسح FCM Token عند تسجيل الخروج
            $user->update(['fc_token' => null]);

            $user->currentAccessToken()->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Logged out successfully',
            ]);

        } catch (\Exception $e) {
            Log::error("Logout error: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred during logout'
            ], 500);
        }
    }
}

