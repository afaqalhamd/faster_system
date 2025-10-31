<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\FirebaseNotificationService;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Exception;

class FirebaseTestController extends Controller
{
    protected $firebaseService;

    public function __construct(FirebaseNotificationService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    /**
     * Test Firebase connection
     *
     * @return JsonResponse
     */
    public function testConnection(): JsonResponse
    {
        try {
            // Test Firebase connection
            $firebase = Firebase::project();
            $projectId = config('firebase.project_id', config('services.firebase.project_id'));

            return response()->json([
                'status' => true,
                'message' => 'Firebase connection successful!',
                'data' => [
                    'project_id' => $projectId,
                    'database_url' => config('firebase.database.url'),
                    'storage_bucket' => config('firebase.storage.default_bucket'),
                    'timestamp' => now()->toDateTimeString(),
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Firebase connection failed: ' . $e->getMessage(),
                'error_details' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]
            ], 500);
        }
    }

    /**
     * Test Firebase Cloud Messaging
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function testMessaging(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'token' => 'required|string',
                'title' => 'required|string',
                'body' => 'required|string',
            ]);

            $result = $this->firebaseService->sendNotificationToDevice(
                $request->token,
                $request->title,
                $request->body,
                [
                    'type' => 'test',
                    'timestamp' => now()->toDateTimeString(),
                    'from' => 'Laravel Backend'
                ]
            );

            return response()->json([
                'status' => true,
                'message' => 'Test notification sent successfully!',
                'firebase_result' => $result
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to send test notification: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Firebase configuration info
     *
     * @return JsonResponse
     */
    public function getConfig(): JsonResponse
    {
        try {
            $config = [
                'project_id' => config('services.firebase.project_id'),
                'database_url' => config('services.firebase.database_url'),
                'storage_bucket' => config('services.firebase.storage_bucket'),
                'auth_domain' => config('services.firebase.auth_domain'),
                'sender_id' => config('services.firebase.sender_id'),
                'has_credentials_file' => file_exists(config('firebase.credentials.file')),
                'credentials_file_path' => config('firebase.credentials.file'),
            ];

            return response()->json([
                'status' => true,
                'message' => 'Firebase configuration retrieved',
                'config' => $config
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to get Firebase config: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test bulk notification sending
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function testBulkMessaging(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'tokens' => 'required|array|min:1',
                'tokens.*' => 'string',
                'title' => 'required|string',
                'body' => 'required|string',
            ]);

            $result = $this->firebaseService->sendNotificationToMultipleDevices(
                $request->tokens,
                $request->title,
                $request->body,
                [
                    'type' => 'bulk_test',
                    'timestamp' => now()->toDateTimeString(),
                    'from' => 'Laravel Backend',
                    'device_count' => count($request->tokens)
                ]
            );

            return response()->json([
                'status' => true,
                'message' => 'Bulk test notifications sent successfully!',
                'target_devices' => count($request->tokens),
                'firebase_result' => $result
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to send bulk notifications: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate Firebase service account
     *
     * @return JsonResponse
     */
    public function validateServiceAccount(): JsonResponse
    {
        try {
            $credentialsPath = config('firebase.credentials.file');

            if (!file_exists($credentialsPath)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Service account file not found',
                    'expected_path' => $credentialsPath
                ], 404);
            }

            $credentials = json_decode(file_get_contents($credentialsPath), true);

            if (!$credentials) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid service account JSON file'
                ], 400);
            }

            $requiredFields = ['type', 'project_id', 'private_key_id', 'private_key', 'client_email'];
            $missingFields = [];

            foreach ($requiredFields as $field) {
                if (!isset($credentials[$field])) {
                    $missingFields[] = $field;
                }
            }

            if (!empty($missingFields)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Service account file is missing required fields',
                    'missing_fields' => $missingFields
                ], 400);
            }

            return response()->json([
                'status' => true,
                'message' => 'Service account file is valid',
                'data' => [
                    'project_id' => $credentials['project_id'],
                    'client_email' => $credentials['client_email'],
                    'type' => $credentials['type'],
                    'file_size' => filesize($credentialsPath) . ' bytes',
                    'last_modified' => date('Y-m-d H:i:s', filemtime($credentialsPath))
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to validate service account: ' . $e->getMessage()
            ], 500);
        }
    }
}
