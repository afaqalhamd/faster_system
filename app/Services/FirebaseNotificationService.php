<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Illuminate\Support\Facades\Log;
use Exception;

class FirebaseNotificationService
{
    private $messaging;

    public function __construct()
    {
        $this->initializeFirebase();
    }

    /**
     * Initialize Firebase with explicit configuration
     */
    private function initializeFirebase()
    {
        try {
            $serviceAccountPath = storage_path('app/faster-delivery-system-firebase-adminsdk.json');

            if (!file_exists($serviceAccountPath)) {
                throw new Exception('Firebase service account file not found');
            }

            $factory = (new Factory)
                ->withServiceAccount($serviceAccountPath)
                ->withProjectId(config('firebase.project_id', 'faster-delivery-system'));

            $this->messaging = $factory->createMessaging();

            Log::info('Firebase initialized successfully', [
                'project_id' => config('firebase.project_id'),
                'service_account_file' => $serviceAccountPath
            ]);

        } catch (Exception $e) {
            Log::error('Firebase initialization failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
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
        try {
            if (!$this->messaging) {
                throw new Exception('Firebase messaging not initialized');
            }

            if (empty($token)) {
                throw new Exception('FCM token is required');
            }

            $notification = Notification::create($title, $body);

            $message = CloudMessage::withTarget('token', $token)
                ->withNotification($notification)
                ->withData($data);

            $result = $this->messaging->send($message);

            Log::info('Firebase notification sent successfully', [
                'token' => substr($token, 0, 20) . '...',
                'title' => $title,
                'body' => $body
            ]);

            return $result;

        } catch (Exception $e) {
            Log::error('Failed to send Firebase notification', [
                'token' => substr($token ?? '', 0, 20) . '...',
                'title' => $title,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
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
        try {
            if (!$this->messaging) {
                throw new Exception('Firebase messaging not initialized');
            }

            if (empty($tokens)) {
                throw new Exception('FCM tokens are required');
            }

            $notification = Notification::create($title, $body);

            $message = CloudMessage::new()
                ->withNotification($notification)
                ->withData($data);

            $result = $this->messaging->sendMulticast($message, $tokens);

            Log::info('Firebase multicast notification sent', [
                'tokens_count' => count($tokens),
                'title' => $title,
                'body' => $body
            ]);

            return $result;

        } catch (Exception $e) {
            Log::error('Failed to send Firebase multicast notification', [
                'tokens_count' => count($tokens ?? []),
                'title' => $title,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
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
        try {
            if (!$this->messaging) {
                throw new Exception('Firebase messaging not initialized');
            }

            if (empty($topic)) {
                throw new Exception('Topic is required');
            }

            $notification = Notification::create($title, $body);

            $message = CloudMessage::withTarget('topic', $topic)
                ->withNotification($notification)
                ->withData($data);

            $result = $this->messaging->send($message);

            Log::info('Firebase topic notification sent successfully', [
                'topic' => $topic,
                'title' => $title,
                'body' => $body
            ]);

            return $result;

        } catch (Exception $e) {
            Log::error('Failed to send Firebase topic notification', [
                'topic' => $topic,
                'title' => $title,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Test Firebase connection
     *
     * @return array
     */
    public function testConnection()
    {
        try {
            if (!$this->messaging) {
                throw new Exception('Firebase messaging not initialized');
            }

            // Try to validate a dummy token to test connection
            // This will return an error for invalid token but confirms Firebase is working
            $testToken = 'test_token_for_connection_validation';

            try {
                $this->messaging->validateRegistrationTokens([$testToken]);
            } catch (Exception $e) {
                // Expected to fail with invalid token, but confirms connection works
                if (strpos($e->getMessage(), 'Invalid registration token') !== false) {
                    return [
                        'success' => true,
                        'message' => 'Firebase connection is working',
                        'project_id' => config('firebase.project_id')
                    ];
                }
                throw $e;
            }

            return [
                'success' => true,
                'message' => 'Firebase connection is working',
                'project_id' => config('firebase.project_id')
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Firebase connection failed: ' . $e->getMessage(),
                'project_id' => config('firebase.project_id')
            ];
        }
    }
}
