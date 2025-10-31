<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\Party\Party;
use App\Http\Requests\Customer\RegisterRequest;
use App\Http\Requests\Customer\LoginRequest;
use App\Http\Requests\Customer\ForgotPasswordRequest;
use App\Http\Requests\Customer\ResetPasswordRequest;
use App\Services\PartyPasswordResetService;
use App\Services\OtpService;
use App\Mail\WelcomeCustomerMail;
use App\Mail\PartyPasswordResetMail;
use App\Mail\VerifyEmailMail;
use App\Http\Requests\Customer\VerifyOtpRequest;
use Carbon\Carbon;

class AuthController extends Controller
{
    protected $passwordResetService;
    protected $otpService;

    public function __construct(
        PartyPasswordResetService $passwordResetService,
        OtpService $otpService
    ) {
        $this->passwordResetService = $passwordResetService;
        $this->otpService = $otpService;
    }

    /**
     * Register a new customer
     *
     * @param RegisterRequest $request
     * @return JsonResponse
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            // Create new party (customer)
            $party = Party::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'mobile' => $request->mobile,
                'password' => $request->password, // Will be hashed automatically
                'phone' => $request->phone,
                'whatsapp' => $request->whatsapp,
                'billing_address' => $request->billing_address,
                'shipping_address' => $request->shipping_address,
                'party_type' => 'customer', // Set as customer
                'status' => 1, // Active by default
                'fc_token' => $request->fc_token,
                'currency_id' => 1, // Default currency (SAR)
                'created_by' => 1, // System user
                'updated_by' => 1, // System user
            ]);

            // Create access token
            $token = $party->createToken('customer-app-token')->plainTextToken;

            // Send OTP for email verification
            $otpResult = $this->otpService->generateAndSendOtp($party);

            // Send welcome email
            try {
                Mail::to($party->email)->send(new WelcomeCustomerMail($party));
            } catch (\Exception $e) {
                Log::warning('Failed to send welcome email', ['error' => $e->getMessage()]);
            }

            // Log successful registration
            Log::info('Customer registered successfully', [
                'party_id' => $party->id,
                'email' => $party->email,
                'otp_sent' => $otpResult['success']
            ]);

            return response()->json([
                'status' => true,
                'message' => 'تم التسجيل بنجاح. يرجى التحقق من بريدك الإلكتروني لإدخال رمز التحقق.',
                'data' => [
                    'customer' => [
                        'id' => $party->id,
                        'full_name' => $party->full_name,
                        'first_name' => $party->first_name,
                        'last_name' => $party->last_name,
                        'email' => $party->email,
                        'mobile' => $party->mobile,
                        'party_type' => $party->party_type,
                        'status' => $party->status,
                        'email_verified' => false
                    ],
                    'token' => $token,
                    'otp_sent' => $otpResult['success'],
                    'otp_expires_in_minutes' => $otpResult['expires_in_minutes'] ?? null
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Customer registration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء التسجيل. يرجى المحاولة لاحقاً'
            ], 500);
        }
    }


    /**
     * Customer login
     *
     * @param LoginRequest $request
     * @return JsonResponse
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            // Find party by email
            $party = Party::where('email', $request->email)->first();

            // Check if party exists and password is correct
            if (!$party || !Hash::check($request->password, $party->password)) {
                Log::warning('Customer login failed - Invalid credentials', [
                    'email' => $request->email,
                    'ip' => $request->ip()
                ]);

                return response()->json([
                    'status' => false,
                    'message' => 'بيانات الاعتماد غير صحيحة'
                ], 401);
            }

            // Check if account is active
            if (!$party->status) {
                Log::warning('Customer login failed - Inactive account', [
                    'party_id' => $party->id,
                    'email' => $party->email
                ]);

                return response()->json([
                    'status' => false,
                    'message' => 'حسابك غير نشط. يرجى التواصل مع الدعم الفني'
                ], 403);
            }

            // Update fc_token if provided
            if ($request->fc_token && $request->fc_token != $party->fc_token) {
                $party->fc_token = $request->fc_token;
                Log::info('Customer fc_token updated', [
                    'party_id' => $party->id,
                    'fc_token' => substr($request->fc_token, 0, 20) . '...'
                ]);
            }

            // Update last login timestamp without triggering observers
            // Using saveQuietly to avoid foreign key constraint error with updated_by
            $party->last_login_at = Carbon::now();
            $party->saveQuietly();

            // Create access token
            $token = $party->createToken('customer-app-token')->plainTextToken;

            Log::info('Customer login successful', [
                'party_id' => $party->id,
                'email' => $party->email
            ]);

            return response()->json([
                'status' => true,
                'message' => 'تم تسجيل الدخول بنجاح',
                'data' => [
                    'customer' => [
                        'id' => $party->id,
                        'full_name' => $party->full_name,
                        'first_name' => $party->first_name,
                        'last_name' => $party->last_name,
                        'email' => $party->email,
                        'mobile' => $party->mobile,
                        'party_type' => $party->party_type,
                        'status' => $party->status,
                        'fc_token'=> $party->fc_token,
                        'email_verified_at' => $party->email_verified_at,
                        'last_login_at' => $party->last_login_at,
                        'balance' => [
                            'to_pay' => $party->to_pay,
                            'to_receive' => $party->to_receive,
                            'net_balance' => $party->net_balance,
                            'credit_limit' => $party->credit_limit,
                            'available_credit' => $party->available_credit,
                            'currency' => $party->currency ? [
                                'id' => $party->currency->id,
                                'code' => $party->currency->code,
                                'symbol' => $party->currency->symbol,
                            ] : null
                        ]
                    ],
                    'token' => $token
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Customer login exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء تسجيل الدخول'
            ], 500);
        }
    }

    /**
     * Customer logout
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $party = $request->user();

            // Clear fc_token without triggering observers
            $party->fc_token = null;
            $party->saveQuietly();

            // Delete current access token
            $request->user()->currentAccessToken()->delete();

            Log::info('Customer logout successful', [
                'party_id' => $party->id
            ]);

            return response()->json([
                'status' => true,
                'message' => 'تم تسجيل الخروج بنجاح'
            ]);

        } catch (\Exception $e) {
            Log::error('Customer logout exception', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء تسجيل الخروج'
            ], 500);
        }
    }

    /**
     * Send password reset link
     *
     * @param ForgotPasswordRequest $request
     * @return JsonResponse
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        try {
            $party = Party::where('email', $request->email)->first();

            if (!$party) {
                // Return generic message for security
                return response()->json([
                    'status' => true,
                    'message' => 'إذا كان البريد الإلكتروني موجوداً، سيتم إرسال رابط إعادة تعيين كلمة المرور'
                ]);
            }

            // Create reset token
            $token = $this->passwordResetService->createResetToken($request->email);

            // Send password reset email
            $resetLink = url('/customer/reset-password?token=' . $token . '&email=' . $request->email);
            try {
                Mail::to($party->email)->send(new PartyPasswordResetMail($party, $resetLink));
            } catch (\Exception $e) {
                Log::warning('Failed to send password reset email', ['error' => $e->getMessage()]);
            }

            Log::info('Password reset requested', [
                'email' => $request->email
            ]);

            return response()->json([
                'status' => true,
                'message' => 'تم إرسال رابط إعادة تعيين كلمة المرور إلى بريدك الإلكتروني',
                'data' => [
                    'token' => $token // TODO: Remove this in production, only for testing
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Forgot password exception', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء معالجة الطلب'
            ], 500);
        }
    }


    /**
     * Reset password using token
     *
     * @param ResetPasswordRequest $request
     * @return JsonResponse
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        try {
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

            Log::info('Password reset successful', [
                'email' => $request->email
            ]);

            return response()->json([
                'status' => true,
                'message' => 'تم إعادة تعيين كلمة المرور بنجاح. يمكنك الآن تسجيل الدخول'
            ]);

        } catch (\Exception $e) {
            Log::error('Reset password exception', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء إعادة تعيين كلمة المرور'
            ], 500);
        }
    }


    /**
     * Verify email address
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function verifyEmail(Request $request): JsonResponse
    {
        try {
            $party = Party::findOrFail($request->route('id'));

            // Check if already verified
            if ($party->email_verified_at) {
                return response()->json([
                    'status' => true,
                    'message' => 'البريد الإلكتروني محقق بالفعل'
                ]);
            }

            // Verify the hash
            if (!hash_equals((string) $request->route('hash'), sha1($party->email))) {
                return response()->json([
                    'status' => false,
                    'message' => 'رابط التحقق غير صحيح'
                ], 400);
            }

            // Mark email as verified without triggering observers
            // Using saveQuietly to avoid foreign key constraint error with updated_by
            $party->email_verified_at = Carbon::now();
            $party->saveQuietly();

            Log::info('Email verified successfully', [
                'party_id' => $party->id
            ]);

            return response()->json([
                'status' => true,
                'message' => 'تم التحقق من البريد الإلكتروني بنجاح'
            ]);

        } catch (\Exception $e) {
            Log::error('Email verification exception', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء التحقق من البريد الإلكتروني'
            ], 500);
        }
    }

    /**
     * Resend email verification link
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function resendVerification(Request $request): JsonResponse
    {
        try {
            $party = $request->user();

            // Check if already verified
            if ($party->email_verified_at) {
                return response()->json([
                    'status' => true,
                    'message' => 'البريد الإلكتروني محقق بالفعل'
                ]);
            }

            // Send verification email
            $verificationUrl = url('/api/customer/auth/verify-email/' . $party->id . '/' . sha1($party->email));
            try {
                Mail::to($party->email)->send(new VerifyEmailMail($party, $verificationUrl));
            } catch (\Exception $e) {
                Log::warning('Failed to send verification email', ['error' => $e->getMessage()]);
            }

            Log::info('Verification email resent', [
                'party_id' => $party->id
            ]);

            return response()->json([
                'status' => true,
                'message' => 'تم إرسال رابط التحقق إلى بريدك الإلكتروني'
            ]);

        } catch (\Exception $e) {
            Log::error('Resend verification exception', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء إرسال رابط التحقق'
            ], 500);
        }
    }

    /**
     * Send OTP for email verification
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function sendOtp(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'المستخدم غير مصرح له'
                ], 401);
            }

            if ($user->hasVerifiedEmail()) {
                return response()->json([
                    'status' => false,
                    'message' => 'البريد الإلكتروني محقق بالفعل'
                ], 400);
            }

            $result = $this->otpService->generateAndSendOtp($user);

            return response()->json([
                'status' => $result['success'],
                'message' => $result['message'],
                'data' => $result['success'] ? [
                    'expires_in_minutes' => $result['expires_in_minutes'] ?? null
                ] : null
            ], $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            Log::error('Failed to send OTP', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء إرسال رمز التحقق'
            ], 500);
        }
    }

    /**
     * Verify OTP for email verification
     *
     * @param VerifyOtpRequest $request
     * @return JsonResponse
     */
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'المستخدم غير مصرح له'
                ], 401);
            }

            if ($user->hasVerifiedEmail()) {
                return response()->json([
                    'status' => false,
                    'message' => 'البريد الإلكتروني محقق بالفعل'
                ], 400);
            }

            $result = $this->otpService->verifyOtp($user, $request->otp);

            $responseData = [
                'status' => $result['success'],
                'message' => $result['message']
            ];

            if ($result['success']) {
                $responseData['data'] = [
                    'email_verified' => true,
                    'verified_at' => $user->fresh()->email_verified_at
                ];
            }

            return response()->json($responseData, $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            Log::error('Failed to verify OTP', [
                'user_id' => $request->user()?->id,
                'otp' => $request->otp,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء التحقق من الرمز'
            ], 500);
        }
    }

    /**
     * Get OTP status and resend cooldown
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getOtpStatus(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'المستخدم غير مصرح له'
                ], 401);
            }

            $cooldown = $this->otpService->getResendCooldown($user);

            return response()->json([
                'status' => true,
                'data' => [
                    'email_verified' => $user->hasVerifiedEmail(),
                    'can_resend' => $cooldown === 0,
                    'resend_cooldown_seconds' => $cooldown,
                    'email' => $user->email
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get OTP status', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء جلب حالة التحقق'
            ], 500);
        }
    }

    /**
     * Delete customer account (Anonymize personal data)
     * Required for Google Play data deletion policy
     *
     * This method anonymizes personal data instead of deleting the record
     * to preserve order and transaction history
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required|string'
            ]);

            // Find party by email
            $party = Party::where('email', $request->email)
                ->where('party_type', 'customer')
                ->first();

            // Check if party exists and password is correct
            if (!$party || !Hash::check($request->password, $party->password)) {
                Log::warning('Account deletion failed - Invalid credentials', [
                    'email' => $request->email,
                    'ip' => $request->ip()
                ]);

                return response()->json([
                    'status' => false,
                    'message' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة'
                ], 401);
            }

            // Check if party has active orders or transactions
            $hasSaleOrders = $party->saleOrders()->exists();
            $hasTransactions = $party->transaction()->exists();

            // Log account deletion/anonymization
            Log::info('Customer account deletion initiated', [
                'party_id' => $party->id,
                'email' => $party->email,
                'has_orders' => $hasSaleOrders,
                'has_transactions' => $hasTransactions,
                'ip' => $request->ip()
            ]);

            // Delete all tokens
            $party->tokens()->delete();

            // Delete support tickets (has cascade)
            $party->supportTickets()->delete();

            // Delete media (profile images)
            $party->clearMediaCollection('profile_image');

            if ($hasSaleOrders || $hasTransactions) {
                // Anonymize personal data instead of deleting
                // This preserves order and transaction history
                $anonymizedEmail = 'deleted_' . $party->id . '_' . time() . '@deleted.local';

                $party->first_name = 'عميل';
                $party->last_name = 'محذوف';
                $party->email = $anonymizedEmail;
                $party->mobile = null;
                $party->phone = null;
                $party->whatsapp = null;
                $party->tax_number = null;
                $party->shipping_address = null;
                $party->billing_address = null;
                $party->password = Hash::make(bin2hex(random_bytes(32))); // Random unguessable password
                $party->fc_token = null;
                $party->email_verified_at = null;
                $party->status = 0; // Deactivate account
                $party->saveQuietly();

                Log::info('Customer account anonymized (has orders/transactions)', [
                    'party_id' => $party->id,
                    'anonymized_email' => $anonymizedEmail
                ]);

                return response()->json([
                    'status' => true,
                    'message' => 'تم حذف بياناتك الشخصية بنجاح. تم الاحتفاظ بسجلات الطلبات والمعاملات المالية للأغراض القانونية.'
                ]);
            } else {
                // Complete deletion if no orders or transactions
                $party->delete();

                Log::info('Customer account deleted completely (no orders/transactions)', [
                    'party_id' => $party->id
                ]);

                return response()->json([
                    'status' => true,
                    'message' => 'تم حذف حسابك بنجاح'
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Account deletion exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء حذف الحساب. يرجى المحاولة لاحقاً'
            ], 500);
        }
    }
}
