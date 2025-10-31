<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Http\Requests\Customer\UpdateProfileRequest;
use App\Http\Requests\Customer\ChangePasswordRequest;
use App\Services\PartyService;

class ProfileController extends Controller
{
    protected $partyService;

    public function __construct(PartyService $partyService)
    {
        $this->partyService = $partyService;
    }
    /**
     * Show customer profile
     * Updated to use PartyService for accurate balance calculation
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function show(Request $request): JsonResponse
    {
        try {
            $party = $request->user();

            // استخدام Cache لمدة 5 دقائق لتحسين الأداء
            $cacheKey = "party_balance_{$party->id}";
            $cacheDuration = 300; // 5 دقائق

            $balanceData = Cache::remember($cacheKey, $cacheDuration, function () use ($party) {
                return $this->partyService->getPartyBalance($party->id);
            });

            // تحديد to_pay و to_receive بناءً على الرصيد الفعلي
            $to_pay = 0;
            $to_receive = 0;
            $net_balance = 0;

            if ($balanceData['status'] === 'you_collect') {
                // الشركة تستحق من العميل (العميل مدين)
                $to_receive = $balanceData['balance'];
                // net_balance سالب للعميل المدين (اللون الأحمر سيوضح ذلك)
                $net_balance = -$balanceData['balance'];
            } elseif ($balanceData['status'] === 'you_pay') {
                // الشركة مدينة للعميل (العميل دائن)
                $to_pay = $balanceData['balance'];
                // net_balance موجب للعميل الدائن (اللون الأخضر سيوضح ذلك)
                $net_balance = $balanceData['balance'];
            }

            // حساب الائتمان المتاح
            $available_credit = $party->is_set_credit_limit
                ? max(0, $party->credit_limit - $to_pay)
                : null;

            return response()->json([
                'status' => true,
                'data' => [
                    'customer' => [
                        'id' => $party->id,
                        'full_name' => $party->full_name,
                        'first_name' => $party->first_name,
                        'last_name' => $party->last_name,
                        'email' => $party->email,
                        'mobile' => $party->mobile,
                        'phone' => $party->phone,
                        'whatsapp' => $party->whatsapp,
                        'party_type' => $party->party_type,
                        'billing_address' => $party->billing_address,
                        'shipping_address' => $party->shipping_address,
                        'tax_number' => $party->tax_number,
                        'status' => $party->status,
                        'email_verified_at' => $party->email_verified_at,
                        'last_login_at' => $party->last_login_at,
                        'profile_image_url' => $party->profile_image_url,
                        'balance' => [
                            // الأرصدة الأساسية (محسوبة من جميع المصادر)
                            'to_pay' => round($to_pay, 2),
                            'to_receive' => round($to_receive, 2),
                            'net_balance' => round($net_balance, 2),

                            // حالة الرصيد
                            'balance_status' => $balanceData['status'],
                            'balance_status_text' => $this->getBalanceStatusText($balanceData['status']),

                            // معلومات الائتمان
                            'credit_limit' => $party->is_set_credit_limit ? round($party->credit_limit, 2) : null,
                            'available_credit' => $available_credit ? round($available_credit, 2) : null,
                            'is_credit_limit_set' => $party->is_set_credit_limit,

                            // معلومات العملة
                            'currency' => $party->currency ? [
                                'id' => $party->currency->id,
                                'code' => $party->currency->code,
                                'symbol' => $party->currency->symbol,
                                'name' => $party->currency->name,
                            ] : null,

                            // معلومات إضافية
                            'last_updated' => now()->toIso8601String(),
                            'cached' => Cache::has($cacheKey),
                        ],
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Get profile exception', [
                'party_id' => $request->user()->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء جلب البيانات'
            ], 500);
        }
    }

    /**
     * Get balance status text in Arabic from customer perspective
     *
     * Note: Messages are reversed because customer sees from their perspective:
     * - 'you_collect' (company collects) = Customer owes = "عليك رصيد مستحق"
     * - 'you_pay' (company pays) = Customer is owed = "لك رصيد مستحق"
     *
     * @param string $status
     * @return string
     */
    private function getBalanceStatusText(string $status): string
    {
        // عكس الرسائل لأن العميل يرى من وجهة نظره
        return match($status) {
            'you_collect' => 'عليك رصيد مستحق',  // الشركة تستحق = العميل مدين
            'you_pay' => 'لك رصيد مستحق',        // الشركة مدينة = العميل دائن
            'no_balance' => 'لا يوجد رصيد',
            default => 'غير محدد',
        };
    }


    /**
     * Update customer profile
     *
     * @param UpdateProfileRequest $request
     * @return JsonResponse
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        try {
            $party = $request->user();

            // Update only the fields that are present in the request
            $party->update($request->only([
                'first_name',
                'last_name',
                'email',
                'mobile',
                'phone',
                'whatsapp',
                'billing_address',
                'shipping_address',
                 'updated_by'
            ]));

            Log::info('Profile updated successfully', [
                'party_id' => $party->id
            ]);

            return response()->json([
                'status' => true,
                'message' => 'تم تحديث الملف الشخصي بنجاح',
                'data' => [
                    'customer' => [
                        'id' => $party->id,
                        'full_name' => $party->full_name,
                        'first_name' => $party->first_name,
                        'last_name' => $party->last_name,
                        'email' => $party->email,
                        'mobile' => $party->mobile,
                        'phone' => $party->phone,
                        'whatsapp' => $party->whatsapp,
                        'billing_address' => $party->billing_address,
                        'shipping_address' => $party->shipping_address,
                        'profile_image_url' => $party->profile_image_url,
                        'updated_by' => 1,
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Update profile exception', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء تحديث الملف الشخصي'
            ], 500);
        }
    }

    /**
     * Upload or update profile image
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function uploadProfileImage(Request $request): JsonResponse
    {
        try {
            Log::info('Upload profile image request received', [
                'has_file' => $request->hasFile('profile_image'),
                'all_files' => $request->allFiles(),
                'content_type' => $request->header('Content-Type')
            ]);

            $request->validate([
                'profile_image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048'
            ], [
                'profile_image.required' => 'الصورة مطلوبة',
                'profile_image.image' => 'يجب أن يكون الملف صورة',
                'profile_image.mimes' => 'يجب أن تكون الصورة من نوع: jpeg, png, jpg, gif, webp',
                'profile_image.max' => 'يجب ألا يتجاوز حجم الصورة 2 ميجابايت'
            ]);

            $party = $request->user();

            // Add the new profile image (will replace the old one automatically due to singleFile())
            $media = $party->addMediaFromRequest('profile_image')
                ->toMediaCollection('profile_image');

            Log::info('Profile image uploaded successfully', [
                'party_id' => $party->id,
                'media_id' => $media->id
            ]);

            return response()->json([
                'status' => true,
                'message' => 'تم رفع صورة الملف الشخصي بنجاح',
                'data' => [
                    'profile_image_url' => $media->getUrl()
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'خطأ في البيانات المدخلة',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Upload profile image exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء رفع الصورة'
            ], 500);
        }
    }

    /**
     * Delete profile image
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteProfileImage(Request $request): JsonResponse
    {
        try {
            $party = $request->user();

            // Delete the profile image
            $party->clearMediaCollection('profile_image');

            Log::info('Profile image deleted successfully', [
                'party_id' => $party->id
            ]);

            return response()->json([
                'status' => true,
                'message' => 'تم حذف صورة الملف الشخصي بنجاح'
            ]);

        } catch (\Exception $e) {
            Log::error('Delete profile image exception', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء حذف الصورة'
            ], 500);
        }
    }

    /**
     * Change customer password
     *
     * @param ChangePasswordRequest $request
     * @return JsonResponse
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        try {
            $party = $request->user();

            // Verify current password
            if (!Hash::check($request->current_password, $party->password)) {
                return response()->json([
                    'status' => false,
                    'message' => 'كلمة المرور الحالية غير صحيحة'
                ], 400);
            }

            // Update password (will be hashed automatically)
            $party->password = $request->new_password;
            $party->save();

            // Revoke all existing tokens for security
            $party->tokens()->delete();

            // Create new token
            $token = $party->createToken('customer-app-token')->plainTextToken;

            Log::info('Password changed successfully', [
                'party_id' => $party->id
            ]);

            return response()->json([
                'status' => true,
                'message' => 'تم تغيير كلمة المرور بنجاح',
                'data' => [
                    'token' => $token
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Change password exception', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء تغيير كلمة المرور'
            ], 500);
        }
    }
}
