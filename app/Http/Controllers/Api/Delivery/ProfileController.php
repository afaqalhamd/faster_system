<?php

namespace App\Http\Controllers\Api\Delivery;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\Delivery\UpdateProfileRequest;
use App\Http\Requests\Delivery\ChangePasswordRequest;

class ProfileController extends Controller
{
    /**
     * Show delivery user profile
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function show(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Validate user is delivery personnel
            if (!$user || !$user->role || strtolower($user->role->name) !== 'delivery') {
                return response()->json([
                    'status' => false,
                    'message' => 'الوصول غير مصرح به'
                ], 403);
            }

            return response()->json([
                'status' => true,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
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
            Log::error('Get delivery profile exception', [
                'user_id' => $request->user()->id ?? null,
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
     * Update delivery user profile
     *
     * @param UpdateProfileRequest $request
     * @return JsonResponse
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Validate user is delivery personnel
            if (!$user || !$user->role || strtolower($user->role->name) !== 'delivery') {
                return response()->json([
                    'status' => false,
                    'message' => 'الوصول غير مصرح به'
                ], 403);
            }

            // Update only the fields that are present in the request
            $user->update($request->only([
                'first_name',
                'last_name',
                'username',
                'email',
                'mobile'
            ]));

            Log::info('Delivery profile updated successfully', [
                'user_id' => $user->id
            ]);

            // Reload user with relationships
            $user->refresh();
            $user->load('carrier', 'role');

            return response()->json([
                'status' => true,
                'message' => 'تم تحديث الملف الشخصي بنجاح',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'first_name' => $user->first_name ?? '',
                        'last_name' => $user->last_name ?? '',
                        'username' => $user->username,
                        'email' => $user->email,
                        'role_id' => $user->role_id,
                        'carrier_id' => $user->carrier_id,
                        'status' => $user->status ?? 1,
                        'avatar' => $user->avatar,
                        'avatar_url' => $user->avatar ? url('/api/getimage/' . $user->avatar) : null,
                        'mobile' => $user->mobile,
                        'is_allowed_all_warehouses' => (int)($user->is_allowed_all_warehouses ?? 0),
                        'fc_token' => $user->fc_token,
                        'created_at' => $user->created_at ? $user->created_at->toISOString() : now()->toISOString(),
                        'updated_at' => $user->updated_at ? $user->updated_at->toISOString() : now()->toISOString(),
                        'carrier' => $user->carrier ? [
                            'id' => $user->carrier->id,
                            'name' => $user->carrier->name,
                            'email' => !empty($user->carrier->email) ? $user->carrier->email : null,
                            'phone' => !empty($user->carrier->phone) ? $user->carrier->phone : null,
                            'address' => !empty($user->carrier->address) ? $user->carrier->address : null,
                            'status' => $user->carrier->status ?? 1,
                            'created_at' => $user->carrier->created_at ? $user->carrier->created_at->toISOString() : now()->toISOString(),
                            'updated_at' => $user->carrier->updated_at ? $user->carrier->updated_at->toISOString() : now()->toISOString(),
                        ] : null
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Update delivery profile exception', [
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
            Log::info('Upload delivery profile image request received', [
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

            $user = $request->user();

            // Validate user is delivery personnel
            if (!$user || !$user->role || strtolower($user->role->name) !== 'delivery') {
                return response()->json([
                    'status' => false,
                    'message' => 'الوصول غير مصرح به'
                ], 403);
            }

            // Delete old avatar if exists
            if ($user->avatar && Storage::exists('public/images/avatar/' . $user->avatar)) {
                Storage::delete('public/images/avatar/' . $user->avatar);
            }

            // Upload new avatar using Storage
            $file = $request->file('profile_image');
            $filename = $this->uploadImage($file);

            // Update user avatar in database
            $user->avatar = $filename;
            $user->save();

            Log::info('Delivery profile image uploaded successfully', [
                'user_id' => $user->id,
                'filename' => $filename
            ]);

            return response()->json([
                'status' => true,
                'message' => 'تم رفع صورة الملف الشخصي بنجاح',
                'data' => [
                    'profile_image_url' => url('/api/getimage/' . $filename)
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'خطأ في البيانات المدخلة',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Upload delivery profile image exception', [
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
            $user = $request->user();

            // Validate user is delivery personnel
            if (!$user || !$user->role || strtolower($user->role->name) !== 'delivery') {
                return response()->json([
                    'status' => false,
                    'message' => 'الوصول غير مصرح به'
                ], 403);
            }

            // Delete the profile image file from storage
            if ($user->avatar && Storage::exists('public/images/avatar/' . $user->avatar)) {
                Storage::delete('public/images/avatar/' . $user->avatar);
            }

            // Update user avatar in database
            $user->avatar = null;
            $user->save();

            Log::info('Delivery profile image deleted successfully', [
                'user_id' => $user->id
            ]);

            return response()->json([
                'status' => true,
                'message' => 'تم حذف صورة الملف الشخصي بنجاح'
            ]);

        } catch (\Exception $e) {
            Log::error('Delete delivery profile image exception', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء حذف الصورة'
            ], 500);
        }
    }

    /**
     * Change delivery user password
     *
     * @param ChangePasswordRequest $request
     * @return JsonResponse
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Validate user is delivery personnel
            if (!$user || !$user->role || strtolower($user->role->name) !== 'delivery') {
                return response()->json([
                    'status' => false,
                    'message' => 'الوصول غير مصرح به'
                ], 403);
            }

            // Verify current password
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'status' => false,
                    'message' => 'كلمة المرور الحالية غير صحيحة'
                ], 400);
            }

            // Update password
            $user->password = Hash::make($request->new_password);
            $user->save();

            // Revoke all existing tokens for security
            $user->tokens()->delete();

            // Create new token
            $token = $user->createToken('delivery-app-token')->plainTextToken;

            Log::info('Delivery password changed successfully', [
                'user_id' => $user->id
            ]);

            return response()->json([
                'status' => true,
                'message' => 'تم تغيير كلمة المرور بنجاح',
                'data' => [
                    'token' => $token
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Change delivery password exception', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء تغيير كلمة المرور'
            ], 500);
        }
    }

    /**
     * Upload image to storage
     *
     * @param $image
     * @return String
     */
    private function uploadImage($image): String
    {
        // Generate a unique filename for the image
        $filename = uniqid() . '.' . $image->getClientOriginalExtension();
        
        // Save the image to the storage disk
        Storage::putFileAs('public/images/avatar', $image, $filename);
        
        return $filename;
    }
}
