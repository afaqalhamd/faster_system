<?php

namespace App\Http\Controllers\Delivery;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\UserPasswordResetService;
use App\Models\User;

class PasswordResetController extends Controller
{
    protected $passwordResetService;

    public function __construct(UserPasswordResetService $passwordResetService)
    {
        $this->passwordResetService = $passwordResetService;
    }

    /**
     * Show the reset password form
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function showResetForm(Request $request)
    {
        $token = $request->query('token');
        $email = $request->query('email');

        // Validate that token and email are provided
        if (!$token || !$email) {
            return redirect()->route('login')
                ->with('error', 'رابط إعادة تعيين كلمة المرور غير صحيح');
        }

        return view('delivery.reset-password', [
            'token' => $token,
            'email' => $email,
            'request' => $request
        ]);
    }

    /**
     * Handle the password reset
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function reset(Request $request)
    {
        // Validate input
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'token' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.email' => 'البريد الإلكتروني غير صحيح',
            'email.exists' => 'البريد الإلكتروني غير موجود',
            'token.required' => 'رمز التحقق مطلوب',
            'password.required' => 'كلمة المرور مطلوبة',
            'password.min' => 'كلمة المرور يجب أن تكون 6 أحرف على الأقل',
            'password.confirmed' => 'كلمة المرور غير متطابقة',
        ]);

        try {
            // Verify user is delivery personnel
            $user = User::where('email', $request->email)->first();

            if (!$user || !$user->role || strtolower($user->role->name) !== 'delivery') {
                return back()
                    ->withInput($request->only('email'))
                    ->withErrors(['email' => 'هذا الحساب غير مخصص لموظفي التوصيل']);
            }

            // Reset password
            $success = $this->passwordResetService->resetPassword(
                $request->email,
                $request->token,
                $request->password
            );

            if (!$success) {
                return back()
                    ->withInput($request->only('email'))
                    ->withErrors(['token' => 'رمز إعادة التعيين غير صحيح أو منتهي الصلاحية']);
            }

            Log::info('Delivery password reset successful via web', [
                'email' => $request->email,
                'user_id' => $user->id
            ]);

            return redirect()->route('login')
                ->with('success', 'تم إعادة تعيين كلمة المرور بنجاح. يمكنك الآن تسجيل الدخول');

        } catch (\Exception $e) {
            Log::error('Delivery web password reset exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['error' => 'حدث خطأ أثناء إعادة تعيين كلمة المرور. يرجى المحاولة لاحقاً']);
        }
    }
}
