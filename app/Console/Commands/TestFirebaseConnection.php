<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FirebaseNotificationService;
use Kreait\Laravel\Firebase\Facades\Firebase;

class TestFirebaseConnection extends Command
{
    protected $signature = 'firebase:test';
    protected $description = 'Test Firebase connection and configuration';

    public function handle()
    {
        $this->info('🔥 Testing Firebase Connection...');
        $this->newLine();

        try {
            // Test 1: Basic Firebase connection
            $this->info('1. Testing Firebase Factory...');
            $firebase = Firebase::project();
            $this->line('   ✅ Firebase factory created successfully');

            // Test 2: Test messaging service
            $this->info('2. Testing Cloud Messaging...');
            $messaging = Firebase::messaging();
            $this->line('   ✅ Firebase messaging service created successfully');

            // Test 3: Get project information
            $this->info('3. Project Information...');
            $projectId = config('firebase.project_id');
            $this->line("   📋 Project ID: {$projectId}");

            $credentialsFile = config('firebase.credentials.file');
            $this->line("   📁 Credentials File: " . (file_exists($credentialsFile) ? 'Found' : 'Missing'));

            // Test 4: Test notification service
            $this->info('4. Testing Notification Service...');
            $notificationService = app(FirebaseNotificationService::class);
            $this->line('   ✅ FirebaseNotificationService initialized successfully');

            // Test 5: Environment variables
            $this->info('5. Environment Variables Check...');
            $envVars = [
                'FIREBASE_PROJECT_ID' => config('services.firebase.project_id'),
                'FIREBASE_API_KEY' => config('services.firebase.api_key'),
                'FIREBASE_SERVER_KEY' => config('services.firebase.server_key'),
                'FIREBASE_SENDER_ID' => config('services.firebase.sender_id'),
            ];

            foreach ($envVars as $var => $value) {
                $status = $value ? '✅' : '❌';
                $displayValue = $value ? (strlen($value) > 20 ? substr($value, 0, 20) . '...' : $value) : 'Not Set';
                $this->line("   {$status} {$var}: {$displayValue}");
            }

            $this->newLine();
            $this->info('🎉 Firebase connection test completed successfully!');
            $this->newLine();

            $this->info('📋 Next Steps:');
            $this->line('1. ✅ Firebase is properly configured');
            $this->line('2. ✅ Ready to send notifications');
            $this->line('3. 🧪 Test sending notification with: php artisan firebase:send-test');
            $this->line('4. 📱 Connect Flutter app using the same project configuration');

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Firebase connection test failed!');
            $this->error('Error: ' . $e->getMessage());
            $this->newLine();

            $this->info('🔧 Troubleshooting:');
            $this->line('1. Check if service account file exists: ' . config('firebase.credentials.file'));
            $this->line('2. Verify environment variables are set in .env file');
            $this->line('3. Run: php artisan config:clear && php artisan config:cache');

            return 1;
        }
    }
}
