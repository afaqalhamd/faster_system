<?php

namespace App\Http\Controllers\Api\Test;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\FirebaseNotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class FcmTokenController extends Controller
{
    private $firebaseNotificationService;

    public function __construct(FirebaseNotificationService $firebaseNotificationService)
    {
        $this->firebaseNotificationService = $firebaseNotificationService;
    }

    /**
     * Update FCM token for the authenticated user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateToken(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'fc_token' => 'required|string|min:10'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();
            $newToken = $request->fc_token;

            // Update user's FCM token
            $user->update(['fc_token' => $newToken]);

            return response()->json([
                'status' => 'success',
                'message' => 'FCM token updated successfully',
                'data' => [
                    'user_id' => $user->id,
                    'token_length' => strlen($newToken),
                    'updated_at' => now()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update FCM token: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test notification with current user's FCM token
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function testNotification(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user->fc_token) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No FCM token found for user'
                ], 400);
            }

            $title = $request->get('title', 'اختبار الإشعار - Test Notification');
            $body = $request->get('body', 'هذا اختبار للإشعارات - This is a notification test');
            $data = [
                'type' => 'manual_test',
                'user_id' => (string) $user->id,
                'timestamp' => now()->toISOString()
            ];

            $result = $this->firebaseNotificationService->sendNotificationToDevice(
                $user->fc_token,
                $title,
                $body,
                $data
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Test notification sent successfully',
                'data' => [
                    'user_id' => $user->id,
                    'user_name' => trim($user->first_name . ' ' . $user->last_name),
                    'title' => $title,
                    'body' => $body,
                    'firebase_result' => $result
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send test notification: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get current user's FCM token info
     *
     * @return JsonResponse
     */
    public function getTokenInfo(): JsonResponse
    {
        try {
            $user = Auth::user();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'user_id' => $user->id,
                    'user_name' => trim($user->first_name . ' ' . $user->last_name),
                    'email' => $user->email,
                    'has_fc_token' => !empty($user->fc_token),
                    'token_length' => $user->fc_token ? strlen($user->fc_token) : 0,
                    'token_preview' => $user->fc_token ? substr($user->fc_token, 0, 20) . '...' : null,
                    'carrier_id' => $user->carrier_id,
                    'role' => $user->role ? $user->role->name : null,
                    'updated_at' => $user->updated_at
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get token info: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Debug FCM token issues
     *
     * @return JsonResponse
     */
    public function debugToken(): JsonResponse
    {
        try {
            $user = Auth::user();

            $tokenInfo = [
                'user_info' => [
                    'id' => $user->id,
                    'name' => trim($user->first_name . ' ' . $user->last_name),
                    'email' => $user->email,
                    'role' => $user->role ? $user->role->name : null,
                    'carrier_id' => $user->carrier_id,
                    'status' => $user->status ? 'active' : 'inactive'
                ],
                'token_info' => [
                    'has_token' => !empty($user->fc_token),
                    'token_length' => $user->fc_token ? strlen($user->fc_token) : 0,
                    'token_format_valid' => $user->fc_token ? preg_match('/^[a-zA-Z0-9_:-]+$/', $user->fc_token) : false,
                    'token_preview' => $user->fc_token ? substr($user->fc_token, 0, 50) . '...' : null,
                    'last_updated' => $user->updated_at
                ],
                'firebase_config' => [
                    'project_id' => config('firebase.project_id'),
                    'service_account_exists' => file_exists(storage_path('app/faster-delivery-system-firebase-adminsdk.json')),
                    'service_account_path' => storage_path('app/faster-delivery-system-firebase-adminsdk.json')
                ]
            ];

            return response()->json([
                'status' => 'success',
                'data' => $tokenInfo
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Debug failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
