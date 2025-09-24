<?php

namespace App\Http\Controllers\Api\Delivery;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    /**
     * Delivery user login
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        try {
            // Validate input
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string'
                // Removed device_token validation temporarily
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Attempt to authenticate user
            $credentials = $request->only('email', 'password');

            if (!Auth::attempt($credentials)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid credentials'
                ], 401);
            }

            // Get authenticated user
            /** @var User&HasApiTokens $user */
            $user = Auth::user();

            // Check if user has delivery role
            if (!$user->role || strtolower($user->role->name) !== 'delivery') {
                Auth::logout();
                return response()->json([
                    'status' => false,
                    'message' => 'Access denied. User is not a delivery personnel.'
                ], 403);
            }

            // Temporarily disable device token update
            // if ($request->has('device_token')) {
            //     $user->fc_token = $request->device_token;
            //     $user->save();
            // }

            // Create token
            $token = $user->createToken('delivery-app-token')->plainTextToken;

            return response()->json([
                'status' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->username,
                        'email' => $user->email,
                        'phone' => $user->mobile,
                        'avatar' => $user->avatar,
                        'carrier_id' => $user->carrier_id,
                        'carrier_name' => $user->carrier->name ?? null
                    ],
                    'token' => $token
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Login failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get authenticated delivery user profile
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function profile(Request $request): JsonResponse
    {
        try {
            /** @var User&HasApiTokens $user */
            $user = Auth::user();

            // Validate user is delivery personnel
            if (!$user || !$user->role || strtolower($user->role->name) !== 'delivery') {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            return response()->json([
                'status' => true,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->username,
                        'email' => $user->email,
                        'phone' => $user->mobile,
                        'avatar' => $user->avatar,
                        'carrier_id' => $user->carrier_id,
                        'carrier_name' => $user->carrier->name ?? null
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve profile: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delivery user logout
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            /** @var User&HasApiTokens $user */
            $user = $request->user();

            // Validate user is delivery personnel
            if (!$user || !$user->role || strtolower($user->role->name) !== 'delivery') {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            // Revoke token
            /** @var PersonalAccessToken $token */
            $token = $user->currentAccessToken();
            $token->delete();

            return response()->json([
                'status' => true,
                'message' => 'Logout successful'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Logout failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
