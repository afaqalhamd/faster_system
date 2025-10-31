<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FirebaseNotificationService;
use App\Models\DeviceToken;

class SendTestNotification extends Command
{
    protected $signature = 'firebase:send-test {--token= : FCM token to send to} {--title=Test Notification : Notification title} {--body=This is a test notification from Laravel : Notification body}';
    protected $description = 'Send a test Firebase notification';

    protected $firebaseService;

    public function __construct(FirebaseNotificationService $firebaseService)
    {
        parent::__construct();
        $this->firebaseService = $firebaseService;
    }

    public function handle()
    {
        $this->info('🔥 Sending Test Firebase Notification...');
        $this->newLine();

        try {
            // Get token from option or ask user
            $token = $this->option('token');

            if (!$token) {
                // Try to get active tokens from database
                $activeTokens = DeviceToken::where('is_active', true)->limit(5)->get();

                if ($activeTokens->count() > 0) {
                    $this->info('Available device tokens:');
                    foreach ($activeTokens as $deviceToken) {
                        $this->line("📱 {$deviceToken->device_type} - User {$deviceToken->user_id} - " . substr($deviceToken->token, 0, 20) . '...');
                    }
                    $this->newLine();

                    $useExisting = $this->confirm('Use an existing token from database?');
                    if ($useExisting) {
                        $selectedIndex = $this->choice('Select a token:', $activeTokens->pluck('token')->toArray());
                        $token = $selectedIndex;
                    }
                }

                if (!$token) {
                    $token = $this->ask('Enter FCM token to send notification to:');
                }
            }

            if (!$token) {
                $this->error('❌ No FCM token provided');
                return 1;
            }

            $title = $this->option('title');
            $body = $this->option('body');

            $this->info("📤 Sending notification...");
            $this->line("   📱 Token: " . substr($token, 0, 20) . '...');
            $this->line("   📝 Title: {$title}");
            $this->line("   💬 Body: {$body}");
            $this->newLine();

            // Send notification
            $result = $this->firebaseService->sendNotificationToDevice(
                $token,
                $title,
                $body,
                [
                    'type' => 'test',
                    'timestamp' => now()->toDateTimeString(),
                    'sent_from' => 'Laravel Console',
                    'test' => true
                ]
            );

            $this->info('✅ Notification sent successfully!');
            $this->line("   📊 Result: " . json_encode($result));
            $this->newLine();

            $this->info('🎉 Test notification completed!');
            $this->line('Check your device for the notification.');

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Failed to send test notification!');
            $this->error('Error: ' . $e->getMessage());
            $this->newLine();

            $this->info('🔧 Troubleshooting:');
            $this->line('1. Verify FCM token is valid and active');
            $this->line('2. Check Firebase configuration: php artisan firebase:test');
            $this->line('3. Ensure device has the app installed and permissions granted');

            return 1;
        }
    }
}
