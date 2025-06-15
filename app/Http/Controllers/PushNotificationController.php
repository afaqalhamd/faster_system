<?php

namespace App\Http\Controllers;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class PushNotificationController extends Controller
{
    public function sendPushNotification(): JsonResponse
    {
        try {
            // التحقق من وجود ملف الاعتماد
            $credentialsFile = storage_path('app/authenticationapp-86aae-2d3203e82a6d.json');

            if (!file_exists($credentialsFile)) {
                return response()->json([
                    'status' => false,
                    'message' => 'ملف اعتماد Firebase غير موجود'
                ], 500);
            }

            // إنشاء اتصال مع Firebase
            $firebase = (new Factory)->withServiceAccount($credentialsFile);

            // إنشاء خدمة الرسائل
            $messaging = $firebase->createMessaging();

            // إنشاء رسالة اختبار
            $message = CloudMessage::fromArray([
                'notification' => [
                    'title' => 'اختبار الاتصال بـ Firebase',
                    'body' => 'هذا اختبار للتحقق من صحة الاتصال مع Firebase'
                ],
                'topic' => 'global'
            ]);

            // إرسال الرسالة
            $result = $messaging->send($message);

            return response()->json([
                'status' => true,
                'message' => 'تم الاتصال بـ Firebase بنجاح وإرسال الإشعار',
                'result' => $result
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'فشل الاتصال بـ Firebase: ' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    /**
     * التحقق فقط من صحة الاتصال بـ Firebase دون إرسال إشعار
     */
    public function checkFirebaseConnection(): JsonResponse
    {
        try {
            $credentialsFile = storage_path('app/authenticationapp-86aae-2d3203e82a6d.json');

            if (!file_exists($credentialsFile)) {
                return response()->json([
                    'status' => false,
                    'message' => 'ملف اعتماد Firebase غير موجود'
                ], 500);
            }

            $firebase = (new Factory)->withServiceAccount($credentialsFile);

            // محاولة الوصول إلى خدمة الرسائل للتحقق من الاتصال
            $messaging = $firebase->createMessaging();

            return response()->json([
                'status' => true,
                'message' => 'تم الاتصال بـ Firebase بنجاح',
                'credentials_file' => $credentialsFile
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'فشل الاتصال بـ Firebase: ' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    /**
     * إرسال إشعار مخصص إلى Firebase
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function sendCustomNotification(Request $request): JsonResponse
    {
        try {
            // التحقق من البيانات المدخلة
            $request->validate([
                'title' => 'required|string|max:255',
                'body' => 'required|string',
                'topic' => 'nullable|string',
                'token' => 'nullable|string',
            ]);

            // التحقق من وجود ملف الاعتماد
            $credentialsFile = storage_path('app/authenticationapp-86aae-2d3203e82a6d.json');

            if (!file_exists($credentialsFile)) {
                return response()->json([
                    'status' => false,
                    'message' => 'ملف اعتماد Firebase غير موجود'
                ], 500);
            }

            // إنشاء اتصال مع Firebase
            $firebase = (new Factory)->withServiceAccount($credentialsFile);

            // إنشاء خدمة الرسائل
            $messaging = $firebase->createMessaging();

            // إعداد الإشعار
            $notification = [
                'title' => $request->title,
                'body' => $request->body,
            ];

            // إضافة بيانات إضافية إذا وجدت
            $data = $request->has('data') ? $request->data : [];

            // إنشاء الرسالة
            if ($request->has('token') && $request->token) {
                // إرسال إلى جهاز محدد
                $message = CloudMessage::withTarget('token', $request->token)
                    ->withNotification($notification)
                    ->withData($data);
            } elseif ($request->has('topic') && $request->topic) {
                // إرسال إلى موضوع
                $message = CloudMessage::withTarget('topic', $request->topic)
                    ->withNotification($notification)
                    ->withData($data);
            } else {
                // إرسال إلى الموضوع العام
                $message = CloudMessage::withTarget('topic', 'global')
                    ->withNotification($notification)
                    ->withData($data);
            }

            // إرسال الرسالة
            $result = $messaging->send($message);

            return response()->json([
                'status' => true,
                'message' => 'تم إرسال الإشعار بنجاح',
                'result' => $result
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'فشل في إرسال الإشعار: ' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }
}