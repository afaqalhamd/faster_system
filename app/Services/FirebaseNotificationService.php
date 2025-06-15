<?php

namespace App\Services;

use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Laravel\Firebase\Facades\Firebase;

class FirebaseNotificationService
{
    /**
     * إرسال إشعار إلى جهاز محدد
     *
     * @param string $token
     * @param string $title
     * @param string $body
     * @param array $data
     * @return array
     */
    public function sendNotificationToDevice($token, $title, $body, $data = [])
    {
        $messaging = Firebase::messaging();

        $notification = Notification::create($title, $body);

        $message = CloudMessage::withTarget('token', $token)
            ->withNotification($notification)
            ->withData($data);

        return $messaging->send($message);
    }

    /**
     * إرسال إشعار إلى مجموعة من الأجهزة
     *
     * @param array $tokens
     * @param string $title
     * @param string $body
     * @param array $data
     * @return array
     */
    public function sendNotificationToMultipleDevices($tokens, $title, $body, $data = [])
    {
        $messaging = Firebase::messaging();

        $notification = Notification::create($title, $body);

        $message = CloudMessage::new()
            ->withNotification($notification)
            ->withData($data);

        return $messaging->sendMulticast($message, $tokens);
    }

    /**
     * إرسال إشعار إلى موضوع معين
     *
     * @param string $topic
     * @param string $title
     * @param string $body
     * @param array $data
     * @return array
     */
    public function sendNotificationToTopic($topic, $title, $body, $data = [])
    {
        $messaging = Firebase::messaging();

        $notification = Notification::create($title, $body);

        $message = CloudMessage::withTarget('topic', $topic)
            ->withNotification($notification)
            ->withData($data);

        return $messaging->send($message);
    }
}