<?php

namespace App\Http\Controllers\Api\Delivery;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;
use App\Http\Requests\Delivery\ForgotPasswordRequest;
use App\Http\Requests\Delivery\ResetPasswordRequest;
use App\Services\UserPasswordResetService;
use App\Mail\UserPasswordResetMail;

class AuthController extends Controller
{
    protected $passwordResetService;

    public function __construct(UserPasswordResetService $passwordResetService)
    {
        $this->passwordResetService = $passwordResetService;
    }

    /**
     * Delivery user login
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        try {
            // Log all incoming request data for debugging
            \Log::info('Delivery Login Request', [
                'all_data' => $request->all(),
                'email' => $request->input('email'),
                'password' => $request->input('password') ? '[HIDDEN]' : null,
                'fc_token' => $request->input('fc_token') ? '[FCM_TOKEN_RECEIVED]' : '[NO_FCM_TOKEN]',
                'headers' => $request->headers->all(),
                'content_type' => $request->header('Content-Type'),
                'method' => $request->method(),
                'ip' => $request->ip()
            ]);

            // Validate input
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string|min:6',
                'fc_token' => 'nullable|string' // Optional FCM token
            ]);

            if ($validator->fails()) {
                \Log::warning('Delivery Login Validation Failed', [
                    'errors' => $validator->errors()->toArray()
                ]);

                return response()->json([
                    'status' => false,
                    'message' => 'البيانات المقدمة غير صحيحة',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Log the credentials being attempted (without password)
            \Log::info('Attempting delivery login', [
                'email' => $request->input('email')
            ]);

            // Attempt to authenticate user
            $credentials = $request->only('email', 'password');

            if (!Auth::attempt($credentials)) {
                \Log::warning('Delivery Login Authentication Failed', [
                    'email' => $request->input('email')
                ]);

                return response()->json([
                    'status' => false,
                    'message' => 'بيانات الاعتماد غير صحيحة'
                ], 401);
            }

            // Get authenticated user
            /** @var User&HasApiTokens $user */
            $user = Auth::user();

            \Log::info('User authenticated', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->role ? $user->role->name : null
            ]);

            // Check if user has delivery role
            if (!$user->role || strtolower($user->role->name) !== 'delivery') {
                Auth::logout();

                \Log::warning('Access denied - Not a delivery user', [
                    'user_id' => $user->id,
                    'role' => $user->role ? $user->role->name : null
                ]);

                return response()->json([
                    'status' => false,
                    'message' => 'الوصول مرفوض. المستخدم ليس من موظفي التوصيل.'
                ], 403);
            }

            // Save FCM token if provided
            if ($request->has('fc_token') && !empty($request->input('fc_token'))) {
                $fcmToken = $request->input('fc_token');

                try {
                    // Update user's fc_token field
                    $user->fc_token = $fcmToken;
                    $user->save();

                    \Log::info('FCM Token saved successfully', [
                        'user_id' => $user->id,
                        'token_length' => strlen($fcmToken)
                    ]);
                } catch (\Exception $fcmException) {
                    // Log error but don't fail the login
                    \Log::error('Failed to save FCM token', [
                        'user_id' => $user->id,
                        'error' => $fcmException->getMessage()
                    ]);
                }
            } else {
                \Log::info('No FCM token provided in login request', [
                    'user_id' => $user->id
                ]);
            }

            // Create token
            $token = $user->createToken('delivery-app-token')->plainTextToken;

            \Log::info('Delivery login successful', [
                'user_id' => $user->id,
                'carrier_id' => $user->carrier_id
            ]);

            return response()->json([
                'status' => true,
                'message' => 'تم تسجيل الدخول بنجاح',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->username,
                        'email' => $user->email,
                        'phone' => $user->mobile,
                        'carrier_id' => $user->carrier_id,
                        'carrier_name' => $user->carrier->name ?? null,
                        'avatar_url' => $user->avatar ? url('/api/getimage/' . $user->avatar) : null
                    ],
                    'token' => $token
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Delivery login exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء تسجيل الدخول: ' . $e->getMessage()
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
                        'carrier_name' => $user->carrier->name ?? null,
                        'avatar_url' => $user->avatar ? url('/api/getimage/' . $user->avatar) : null

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
                'message' => 'حدث خطأ أثناء تسجيل الخروج: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Debug endpoint to test delivery login data reception
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function debugLogin(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'debug',
            'message' => 'Debug delivery login data reception',
            'received_data' => [
                'all_input' => $request->all(),
                'email' => $request->input('email'),
                'password' => $request->has('password') ? '[RECEIVED]' : '[NOT_RECEIVED]',
                'fc_token' => $request->has('fc_token') ? '[FCM_TOKEN_RECEIVED]' : '[NO_FCM_TOKEN]',
                'fc_token_length' => $request->has('fc_token') ? strlen($request->input('fc_token')) : 0,
                'content_type' => $request->header('Content-Type'),
                'method' => $request->method(),
                'headers' => [
                    'authorization' => $request->header('Authorization'),
                    'accept' => $request->header('Accept'),
                    'user_agent' => $request->header('User-Agent')
                ],
                'json_data' => $request->json() ? $request->json()->all() : null,
                'raw_content' => $request->getContent()
            ],
            'validation_check' => [
                'email_valid' => filter_var($request->input('email'), FILTER_VALIDATE_EMAIL) !== false,
                'password_length' => strlen($request->input('password', '')),
                'fc_token_provided' => $request->has('fc_token') && !empty($request->input('fc_token'))
            ]
        ]);
    }

    /**
     * Send password reset link for delivery user
     *
     * @param ForgotPasswordRequest $request
     * @return JsonResponse
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        try {
            Log::info('🔄 Delivery forgot password request received', [
               'email' => $request->email
            ]);

            $user = User::where('email', $request->email)->first();

            if (!$user) {
                Log::warning('⚠️ User not found', ['email' => $request->email]);
                // Return generic message for security
                return response()->json([
                    'status' => true,
                    'message' => 'إذا كان البريد الإلكتروني موجوداً، سيتم إرسال رابط إعادة تعيين كلمة المرور'
                ]);
            }

            Log::info('✅ User found', [
                'user_id' => $user->id,
                'role' => $user->role ? $user->role->name : 'no role'
            ]);

            // Check if user has delivery role
            if (!$user->role || strtolower($user->role->name) !== 'delivery') {
                Log::warning('⚠️ User is not a delivery user', [
                    'user_id' => $user->id,
                    'role' => $user->role ? $user->role->name : 'no role'
                ]);
                // Return generic message for security
                return response()->json([
                    'status' => true,
                    'message' => 'إذا كان البريد الإلكتروني موجوداً، سيتم إرسال رابط إعادة تعيين كلمة المرور'
                ]);
            }

            // Create reset token
            $token = $this->passwordResetService->createResetToken($request->email);
            Log::info('🔑 Reset token created', ['token_length' => strlen($token)]);

            // Send password reset email
            $resetLink = route('delivery.password.reset', ['token' => $token, 'email' => $request->email]);
            Log::info('🔗 Reset link generated', ['link' => $resetLink]);

            try {
                Mail::to($user->email)->send(new UserPasswordResetMail($user, $resetLink));
                Log::info('📧 Password reset email sent successfully', ['to' => $user->email]);
            } catch (\Exception $e) {
                Log::error('❌ Failed to send password reset email', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }

            Log::info('✅ Delivery password reset request completed', [
                'email' => $request->email,
                'user_id' => $user->id
            ]);

            return response()->json([
                'status' => true,
                'message' => 'تم إرسال رابط إعادة تعيين كلمة المرور إلى بريدك الإلكتروني',
                'data' => [
                    'token' => $token, // TODO: Remove this in production, only for testing
                    'reset_link' => $resetLink // For testing purposes
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Delivery forgot password exception', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء معالجة الطلب'
            ], 500);
        }
    }

    /**
     * Reset password using token for delivery user
     *
     * @param ResetPasswordRequest $request
     * @return JsonResponse
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        try {
            // Verify user is delivery personnel
            $user = User::where('email', $request->email)->first();

            if (!$user || !$user->role || strtolower($user->role->name) !== 'delivery') {
                return response()->json([
                    'status' => false,
                    'message' => 'رمز إعادة التعيين غير صحيح أو منتهي الصلاحية'
                ], 400);
            }

            $success = $this->passwordResetService->resetPassword(
                $request->email,
                $request->token,
                $request->password
            );

            if (!$success) {
                return response()->json([
                    'status' => false,
                    'message' => 'رمز إعادة التعيين غير صحيح أو منتهي الصلاحية'
                ], 400);
            }

            Log::info('Delivery password reset successful', [
                'email' => $request->email,
                'user_id' => $user->id
            ]);

            return response()->json([
                'status' => true,
                'message' => 'تم إعادة تعيين كلمة المرور بنجاح. يمكنك الآن تسجيل الدخول'
            ]);

        } catch (\Exception $e) {
            Log::error('Delivery reset password exception', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء إعادة تعيين كلمة المرور'
            ], 500);
        }
    }
}
