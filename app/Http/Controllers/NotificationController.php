<?php

namespace App\Http\Controllers;

use App\Models\DeviceToken;
use App\Models\User;
use App\Services\FirebaseNotificationService;
use Illuminate\Http\Request;
class NotificationController extends Controller
{
    protected $firebaseService;

    public function __construct(FirebaseNotificationService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }




    
    /**
     * عرض صفحة إرسال الإشعارات
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $users = User::all();
        $deviceTokensCount = DeviceToken::where('is_active', true)->count();

        return view('notifications.index', compact('users', 'deviceTokensCount'));
    }

    /**
     * إرسال إشعار تجريبي
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sendNotification(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'user_id' => 'nullable|exists:users,id',
        ]);

        try {
            $title = $request->title;
            $body = $request->body;
            $data = [
                'type' => 'admin_notification',
                'time' => now()->toDateTimeString(),
            ];

            // إذا تم تحديد مستخدم معين
            if ($request->user_id) {
                $tokens = DeviceToken::where('user_id', $request->user_id)
                    ->where('is_active', true)
                    ->pluck('token')
                    ->toArray();

                if (empty($tokens)) {
                    return redirect()->back()->with('error', 'لا يوجد أجهزة نشطة لهذا المستخدم');
                }

                $this->firebaseService->sendNotificationToMultipleDevices($tokens, $title, $body, $data);
                return redirect()->back()->with('success', 'تم إرسال الإشعار بنجاح إلى المستخدم المحدد');
            } else {
                // إرسال إلى جميع المستخدمين
                $tokens = DeviceToken::where('is_active', true)
                    ->pluck('token')
                    ->toArray();

                if (empty($tokens)) {
                    return redirect()->back()->with('error', 'لا يوجد أجهزة نشطة لإرسال الإشعارات إليها');
                }

                $this->firebaseService->sendNotificationToMultipleDevices($tokens, $title, $body, $data);
                return redirect()->back()->with('success', 'تم إرسال الإشعار بنجاح إلى جميع المستخدمين');
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'فشل في إرسال الإشعار: ' . $e->getMessage());
        }
    }
}
