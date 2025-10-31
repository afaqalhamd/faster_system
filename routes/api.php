<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SaleOrderController;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use App\Http\Controllers\PushNotificationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Health Check / Ping Route
Route::get('/ping', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'API is working',
        'timestamp' => now()->toISOString(),
        'server_time' => now()->format('Y-m-d H:i:s'),
    ]);
});

// Authentication Routes
Route::post('/login', [AuthController::class, 'login']);

Route::get('/getimage/{image_name}', function($image_name) {
    $imagePath = 'public/images/avatar/' . $image_name;

    if (!Storage::exists($imagePath)) {
        return response()->json(['error' => 'Image not found'], 404);
    }

    $file = Storage::get($imagePath);
    $type = Storage::mimeType($imagePath);

    return Response::make($file, 200)
        ->header('Content-Type', $type)
        ->header('Cache-Control', 'public, max-age=31536000');
})->where('image_name', '.*');

// Item Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/items', [App\Http\Controllers\Api\ItemController::class, 'index']);
    Route::get('/items/search', [App\Http\Controllers\Api\ItemController::class, 'search']);
    Route::get('/items/category/{categoryId}', [App\Http\Controllers\Api\ItemController::class, 'getItemsByCategory']);
    Route::get('/items/sku/{sku}', [App\Http\Controllers\Api\ItemController::class, 'getItemBySKU']);
    Route::get('/items/{id}', [App\Http\Controllers\Api\ItemController::class, 'show']);
    Route::post('/items', [App\Http\Controllers\Api\ItemController::class, 'store']);
    Route::put('/items/{id}', [App\Http\Controllers\Api\ItemController::class, 'update']);
    // رفع صورة منتج
    Route::post('/items/upload-image', [App\Http\Controllers\Api\ItemController::class, 'uploadItemImage']);
});

// Currency Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/currencies', [App\Http\Controllers\Api\CurrencyController::class, 'index']);
    Route::get('/currencies/{id}', [App\Http\Controllers\Api\CurrencyController::class, 'show']);
    Route::get('/company-currency', [App\Http\Controllers\Api\CurrencyController::class, 'getCompanyCurrency']);
});

// Sale Order Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/sale-orders', [SaleOrderController::class, 'index']);
    Route::get('/sale-orders/{id}', [SaleOrderController::class, 'show']);
    Route::post('/sale-orders', [SaleOrderController::class, 'store']);
    Route::put('/sale-orders/{id}', [SaleOrderController::class, 'update']);
    Route::delete('/sale-orders/{id}', [SaleOrderController::class, 'destroy']);
    Route::post('/sale-orders/{id}/status', [SaleOrderController::class, 'updateStatus']);
    Route::get('/sale-orders/{id}/status-history', [SaleOrderController::class, 'getStatusHistory']);

    // Shipment Tracking Routes
    Route::post('/sale-orders/{saleOrderId}/tracking', [App\Http\Controllers\Api\ShipmentTrackingController::class, 'store']);
    Route::get('/shipment-tracking/{id}', [App\Http\Controllers\Api\ShipmentTrackingController::class, 'show']);
    Route::put('/shipment-tracking/{id}', [App\Http\Controllers\Api\ShipmentTrackingController::class, 'update']);
    Route::delete('/shipment-tracking/{id}', [App\Http\Controllers\Api\ShipmentTrackingController::class, 'destroy']);
    Route::post('/shipment-tracking/{trackingId}/events', [App\Http\Controllers\Api\ShipmentTrackingController::class, 'addEvent']);
    Route::delete('/shipment-events/{eventId}', [App\Http\Controllers\Api\ShipmentTrackingController::class, 'deleteEvent']);
    Route::post('/shipment-tracking/{trackingId}/documents', [App\Http\Controllers\Api\ShipmentTrackingController::class, 'uploadDocument']);
    Route::get('/sale-orders/{saleOrderId}/tracking-history', [App\Http\Controllers\Api\ShipmentTrackingController::class, 'getTrackingHistory']);
    Route::get('/tracking-statuses', [App\Http\Controllers\Api\ShipmentTrackingController::class, 'getStatuses']);
    Route::get('/tracking-document-types', [App\Http\Controllers\Api\ShipmentTrackingController::class, 'getDocumentTypes']);

    // Waybill Validation Routes
    Route::post('/waybill/validate', [App\Http\Controllers\Api\ShipmentTrackingController::class, 'validateWaybill']);
    Route::post('/waybill/validate-barcode', [App\Http\Controllers\Api\ShipmentTrackingController::class, 'validateWaybillBarcode']);
    Route::get('/waybill/rules', [App\Http\Controllers\Api\ShipmentTrackingController::class, 'getWaybillRules']);

    // QR Code Processing Route
    Route::post('/waybill/process-qr', [App\Http\Controllers\Api\ShipmentTrackingController::class, 'processScannedQRCode']);

});

// Delivery API Routes
Route::prefix('delivery')->group(function () {
    // Delivery Authentication
    Route::post('/login', [App\Http\Controllers\Api\Delivery\AuthController::class, 'login']);
    Route::post('/forgot-password', [App\Http\Controllers\Api\Delivery\AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [App\Http\Controllers\Api\Delivery\AuthController::class, 'resetPassword']);

    // Debug endpoint for testing login data reception
    Route::post('/debug-login', [App\Http\Controllers\Api\Delivery\AuthController::class, 'debugLogin']);
    Route::get('/debug-login', [App\Http\Controllers\Api\Delivery\AuthController::class, 'debugLogin']);

    Route::middleware('auth:sanctum')->group(function () {
        // Delivery Profile Management
        Route::get('/profile', [App\Http\Controllers\Api\Delivery\ProfileController::class, 'show']);
        Route::put('/profile', [App\Http\Controllers\Api\Delivery\ProfileController::class, 'update']);
        Route::post('/profile/upload-image', [App\Http\Controllers\Api\Delivery\ProfileController::class, 'uploadProfileImage']);
        Route::delete('/profile/delete-image', [App\Http\Controllers\Api\Delivery\ProfileController::class, 'deleteProfileImage']);
        Route::post('/profile/change-password', [App\Http\Controllers\Api\Delivery\ProfileController::class, 'changePassword']);

        // Delivery Authentication
        Route::post('/logout', [App\Http\Controllers\Api\Delivery\AuthController::class, 'logout']);

        // Delivery Orders
        Route::get('/orders', [App\Http\Controllers\Api\Delivery\OrderController::class, 'index']);
        Route::get('/orders/{id}', [App\Http\Controllers\Api\Delivery\OrderController::class, 'show']);
        Route::put('/orders/{id}', [App\Http\Controllers\Api\Delivery\OrderController::class, 'update']);

        // Delivery Status Management
        Route::get('/statuses', [App\Http\Controllers\Api\Delivery\StatusController::class, 'index']);
        Route::post('/orders/{id}/status', [App\Http\Controllers\Api\Delivery\OrderController::class, 'updateStatus']);
        Route::get('/orders/{id}/status-history', [App\Http\Controllers\Api\Delivery\OrderController::class, 'statusHistory']);

        // Delivery Payment Collection
        Route::get('/orders/{id}/payment', [App\Http\Controllers\Api\Delivery\PaymentController::class, 'show']);
        Route::post('/orders/{id}/payment', [App\Http\Controllers\Api\Delivery\OrderController::class, 'collectPayment']);
        Route::get('/orders/{id}/payment-history', [App\Http\Controllers\Api\Delivery\OrderController::class, 'paymentHistory']);

        // Complete Delivery Process
        Route::post('/orders/{id}/complete-delivery', [App\Http\Controllers\Api\Delivery\OrderController::class, 'completeDelivery']);

        // Device Token Management for Notifications
        Route::prefix('device-tokens')->group(function () {
            Route::post('/register', [App\Http\Controllers\Api\DeviceTokenController::class, 'register']);
            Route::put('/update', [App\Http\Controllers\Api\DeviceTokenController::class, 'update']);
            Route::delete('/deactivate', [App\Http\Controllers\Api\DeviceTokenController::class, 'deactivate']);
            Route::get('/status', [App\Http\Controllers\Api\DeviceTokenController::class, 'status']);
        });
    });
});

// Firebase Testing Routes (Public for testing)
Route::prefix('firebase-test')->group(function () {
    Route::get('/connection', [App\Http\Controllers\FirebaseTestController::class, 'testConnection']);
    Route::get('/config', [App\Http\Controllers\FirebaseTestController::class, 'getConfig']);
    Route::get('/validate-service-account', [App\Http\Controllers\FirebaseTestController::class, 'validateServiceAccount']);
    Route::post('/messaging', [App\Http\Controllers\FirebaseTestController::class, 'testMessaging']);
    Route::post('/bulk-messaging', [App\Http\Controllers\FirebaseTestController::class, 'testBulkMessaging']);
});

// Notification Testing Routes (مسارات اختبار الإشعارات)
Route::group(['prefix' => 'test', 'middleware' => 'auth:sanctum'], function () {
    // Test carrier notifications
    Route::post('/carrier-notification', [\App\Http\Controllers\Api\Test\NotificationTestController::class, 'testCarrierNotification']);

    // Test delivery notification by changing status
    Route::post('/delivery-notification', [\App\Http\Controllers\Api\Test\NotificationTestController::class, 'testDeliveryNotification']);

    // Get delivery users for carrier
    Route::get('/delivery-users', [\App\Http\Controllers\Api\Test\NotificationTestController::class, 'getDeliveryUsers']);

    // Get carriers with delivery users
    Route::get('/carriers-delivery-users', [\App\Http\Controllers\Api\Test\NotificationTestController::class, 'getCarriersWithDeliveryUsers']);

    // Get test sale orders
    Route::get('/sale-orders', [\App\Http\Controllers\Api\Test\NotificationTestController::class, 'getTestSaleOrders']);

    // Simulate status change
    Route::post('/simulate-status-change', [\App\Http\Controllers\Api\Test\NotificationTestController::class, 'simulateStatusChange']);
});

// FCM Token Management Routes (مسارات إدارة رموز FCM)
Route::group(['prefix' => 'fcm', 'middleware' => 'auth:sanctum'], function () {
    // Update FCM token
    Route::put('/update-token', [\App\Http\Controllers\Api\Test\FcmTokenController::class, 'updateToken']);

    // Test notification with current token
    Route::post('/test-notification', [\App\Http\Controllers\Api\Test\FcmTokenController::class, 'testNotification']);

    // Get current token info
    Route::get('/token-info', [\App\Http\Controllers\Api\Test\FcmTokenController::class, 'getTokenInfo']);

    // Debug token issues
    Route::get('/debug-token', [\App\Http\Controllers\Api\Test\FcmTokenController::class, 'debugToken']);
});

// Chat Notification Routes (مسارات إشعارات الشات)
Route::middleware('auth:sanctum')->prefix('chat')->group(function () {
    Route::post('/send-notification', [App\Http\Controllers\Api\ChatNotificationController::class, 'sendChatNotification']);
});

// FCM Token Update Routes (مسارات تحديث FCM Token)
// Commented out - Controller not found
// Route::middleware('auth:sanctum')->prefix('fcm-token')->group(function () {
//     Route::post('/update', [App\Http\Controllers\Api\FcmTokenController::class, 'updateToken']);
//     Route::get('/get', [App\Http\Controllers\Api\FcmTokenController::class, 'getToken']);
// });

// Support Tickets Routes
Route::middleware('auth:sanctum')->prefix('support')->group(function () {
    // Tickets Management
    Route::get('/tickets', [App\Http\Controllers\Api\SupportTicketController::class, 'index']);
    Route::post('/tickets', [App\Http\Controllers\Api\SupportTicketController::class, 'store']);
    Route::get('/tickets/{id}', [App\Http\Controllers\Api\SupportTicketController::class, 'show']);
    Route::post('/tickets/{id}/messages', [App\Http\Controllers\Api\SupportTicketController::class, 'addMessage']);
    Route::post('/tickets/{id}/close', [App\Http\Controllers\Api\SupportTicketController::class, 'close']);
    Route::post('/tickets/{id}/reopen', [App\Http\Controllers\Api\SupportTicketController::class, 'reopen']);

    // Statistics
    Route::get('/tickets/stats/summary', [App\Http\Controllers\Api\SupportTicketController::class, 'getStatistics']);

    // Admin-only routes (admin check is done in controller methods)
    Route::put('/tickets/{id}/status', [App\Http\Controllers\Api\SupportTicketController::class, 'updateTicketStatus']);
    Route::put('/tickets/{id}/priority', [App\Http\Controllers\Api\SupportTicketController::class, 'updateTicketPriority']);
    Route::put('/tickets/{id}/assign', [App\Http\Controllers\Api\SupportTicketController::class, 'assignTicket']);
    Route::post('/tickets/{id}/staff-reply', [App\Http\Controllers\Api\SupportTicketController::class, 'addStaffReply']);
});

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Unit Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::apiResource('units', App\Http\Controllers\Api\UnitController::class);
    });

    // Tax Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::apiResource('taxes', App\Http\Controllers\Api\TaxController::class);
    });

    // Item Category Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::apiResource('item-categories', App\Http\Controllers\Api\ItemCategoryController::class);
    });

    // Sale Order Routes
    Route::apiResource('sale-orders', SaleOrderController::class);
    Route::get('/recent-sale-orders', [App\Http\Controllers\Api\SaleOrderController::class, 'getRecentSaleOrders']);
    Route::get('/sale-orders/{id}/convert', [App\Http\Controllers\Api\SaleOrderController::class, 'convertToSale']);
    Route::get('/sale-orders/{id}/details', [App\Http\Controllers\Api\SaleOrderController::class, 'details']);

    // Delivery-specific routes for carrier-based delivery operations
    Route::get('/delivery-orders', [App\Http\Controllers\Api\SaleOrderController::class, 'deliveryOrders']);
    Route::get('/delivery-orders/{id}', [App\Http\Controllers\Api\SaleOrderController::class, 'deliveryOrderDetails']);
    Route::post('/delivery-orders/{id}/update-status', [App\Http\Controllers\Api\SaleOrderController::class, 'updateDeliveryStatus']);
    Route::post('/delivery-orders/{id}/collect-payment', [App\Http\Controllers\Api\SaleOrderController::class, 'collectDeliveryPayment']);
    Route::get('/delivery-orders/{id}/status-history', [App\Http\Controllers\Api\SaleOrderController::class, 'deliveryOrderStatusHistory']);
    Route::get('/delivery-profile', [App\Http\Controllers\Api\SaleOrderController::class, 'deliveryProfile']);
    Route::get('/delivery-statuses', [App\Http\Controllers\Api\SaleOrderController::class, 'deliveryStatuses']);

    // Other routes
    Route::get('/user-data', [AuthController::class, 'getUserData']);
    Route::post('/sales/simple', [App\Http\Controllers\Api\SaleController::class, 'createSimpleSale']);

    // Item Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/items', [App\Http\Controllers\Api\ItemController::class, 'index']);
        Route::get('/items/search', [App\Http\Controllers\Api\ItemController::class, 'search']);
        Route::get('/items/category/{categoryId}', [App\Http\Controllers\Api\ItemController::class, 'getItemsByCategory']);
        Route::get('/items/sku/{sku}', [App\Http\Controllers\Api\ItemController::class, 'getItemBySKU']);
        Route::get('/items/{id}', [App\Http\Controllers\Api\ItemController::class, 'show']);
        Route::post('/items', [App\Http\Controllers\Api\ItemController::class, 'store']);
        Route::put('/items/{id}', [App\Http\Controllers\Api\ItemController::class, 'update']);
        // رفع صورة منتج
        Route::post('/items/upload-image', [App\Http\Controllers\Api\ItemController::class, 'uploadItemImage']);
    });

    // Currency Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/currencies', [App\Http\Controllers\Api\CurrencyController::class, 'index']);
        Route::get('/currencies/{id}', [App\Http\Controllers\Api\CurrencyController::class, 'show']);
        Route::get('/company-currency', [App\Http\Controllers\Api\CurrencyController::class, 'getCompanyCurrency']);
    });

    // Item Transaction Routes
    Route::middleware('auth:sanctum')->prefix('item-transactions')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\ItemTransactionController::class, 'index']);
        Route::get('/recent', [App\Http\Controllers\Api\ItemTransactionController::class, 'getRecentTransactions']);
        Route::get('/item/{itemId}', [App\Http\Controllers\Api\ItemTransactionController::class, 'getTransactionsByItem']);
        Route::get('/warehouse/{warehouseId}', [App\Http\Controllers\Api\ItemTransactionController::class, 'getTransactionsByWarehouse']);
        Route::get('/{id}', [App\Http\Controllers\Api\ItemTransactionController::class, 'show']);
    });

    // Sale Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::apiResource('sales', App\Http\Controllers\Api\SaleController::class);
    });

    // Sales API Routes - Complete CRUD and Operations
    Route::middleware('auth:sanctum')->prefix('sales-api')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\SaleControllerApi::class, 'index']);
        Route::get('/{id}', [App\Http\Controllers\Api\SaleControllerApi::class, 'show']);
        Route::post('/', [App\Http\Controllers\Api\SaleControllerApi::class, 'store']);
        Route::put('/{id}', [App\Http\Controllers\Api\SaleControllerApi::class, 'update']);
        Route::delete('/{id}', [App\Http\Controllers\Api\SaleControllerApi::class, 'destroy']);
    });
});

// Firebase Testing Routes (Public for testing)
Route::prefix('firebase-test')->group(function () {
    Route::get('/connection', [App\Http\Controllers\FirebaseTestController::class, 'testConnection']);
    Route::get('/config', [App\Http\Controllers\FirebaseTestController::class, 'getConfig']);
    Route::get('/validate-service-account', [App\Http\Controllers\FirebaseTestController::class, 'validateServiceAccount']);
    Route::post('/messaging', [App\Http\Controllers\FirebaseTestController::class, 'testMessaging']);
    Route::post('/bulk-messaging', [App\Http\Controllers\FirebaseTestController::class, 'testBulkMessaging']);
});


// Customer API Routes
Route::prefix('customer')->group(function () {

    // Authentication Routes (Public)
    Route::prefix('auth')->group(function () {
        Route::post('/register', [App\Http\Controllers\Api\Customer\AuthController::class, 'register']);
        Route::post('/login', [App\Http\Controllers\Api\Customer\AuthController::class, 'login'])
            ->middleware('throttle:5,1');
        Route::post('/forgot-password', [App\Http\Controllers\Api\Customer\AuthController::class, 'forgotPassword'])
            ->middleware('throttle:10,1');  // 10 attempts per minute for development
        Route::post('/reset-password', [App\Http\Controllers\Api\Customer\AuthController::class, 'resetPassword']);
        Route::get('/verify-email/{id}/{hash}', [App\Http\Controllers\Api\Customer\AuthController::class, 'verifyEmail'])
            ->name('customer.verification.verify');

        // Account Deletion (Public - for Google Play policy)
        Route::post('/delete-account', [App\Http\Controllers\Api\Customer\AuthController::class, 'deleteAccount'])
            ->middleware('throttle:5,1');
    });

    // Protected Routes
    Route::middleware(['auth:sanctum', 'customer.auth'])->group(function () {

        // Authentication (Protected)
        Route::prefix('auth')->group(function () {
            Route::post('/logout', [App\Http\Controllers\Api\Customer\AuthController::class, 'logout']);
            Route::post('/resend-verification', [App\Http\Controllers\Api\Customer\AuthController::class, 'resendVerification']);

            // OTP Routes
            Route::post('/send-otp', [App\Http\Controllers\Api\Customer\AuthController::class, 'sendOtp'])
                ->middleware('throttle:5,1'); // 5 attempts per minute
            Route::post('/verify-otp', [App\Http\Controllers\Api\Customer\AuthController::class, 'verifyOtp'])
                ->middleware('throttle:10,1'); // 10 attempts per minute
            Route::get('/otp-status', [App\Http\Controllers\Api\Customer\AuthController::class, 'getOtpStatus']);
        });

        // Profile Routes
        Route::prefix('profile')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\Customer\ProfileController::class, 'show']);
            Route::put('/', [App\Http\Controllers\Api\Customer\ProfileController::class, 'update']);
            Route::post('/change-password', [App\Http\Controllers\Api\Customer\ProfileController::class, 'changePassword']);
            Route::post('/profile-image', [App\Http\Controllers\Api\Customer\ProfileController::class, 'uploadProfileImage']);
            Route::delete('/profile-image', [App\Http\Controllers\Api\Customer\ProfileController::class, 'deleteProfileImage']);
        });

        // Order Routes
        Route::prefix('orders')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\Customer\OrderController::class, 'index']);
            Route::get('/{id}', [App\Http\Controllers\Api\Customer\OrderController::class, 'show']);
            Route::get('/{id}/details', [App\Http\Controllers\Api\Customer\OrderController::class, 'details']);
        });

        // Balance Routes
        Route::prefix('balance')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\Customer\BalanceController::class, 'show']);
            Route::get('/transactions', [App\Http\Controllers\Api\Customer\BalanceController::class, 'transactions']);
            Route::get('/breakdown', [App\Http\Controllers\Api\Customer\BalanceController::class, 'breakdown']);
            Route::get('/unpaid-orders', [App\Http\Controllers\Api\Customer\BalanceController::class, 'unpaidOrders']);
            Route::get('/unpaid-invoices', [App\Http\Controllers\Api\Customer\BalanceController::class, 'unpaidInvoices']);
            Route::post('/refresh', [App\Http\Controllers\Api\Customer\BalanceController::class, 'refreshBalance']);
        });

        // Notification Routes
        Route::prefix('notifications')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\Customer\NotificationController::class, 'index']);
            Route::put('/{id}/read', [App\Http\Controllers\Api\Customer\NotificationController::class, 'markAsRead']);
            Route::post('/mark-all-read', [App\Http\Controllers\Api\Customer\NotificationController::class, 'markAllAsRead']);
        });

        // Statistics Routes
        Route::get('/stats', [App\Http\Controllers\Api\Customer\StatsController::class, 'index']);

        // Tracking Routes
        Route::prefix('tracking')->group(function () {
            Route::post('/search', [App\Http\Controllers\Api\ShipmentTrackingController::class, 'searchByTrackingNumber']);
            Route::get('/search', [App\Http\Controllers\Api\ShipmentTrackingController::class, 'searchByTrackingNumber']);
            Route::post('/validate', [App\Http\Controllers\Api\ShipmentTrackingController::class, 'validateTrackingNumber']);
            Route::get('/validate', [App\Http\Controllers\Api\ShipmentTrackingController::class, 'validateTrackingNumber']);
        });
    });

    // Public Tracking Routes (لا تحتاج تسجيل دخول)
    Route::prefix('tracking')->group(function () {
        Route::post('/search-public', [App\Http\Controllers\Api\ShipmentTrackingController::class, 'searchByTrackingNumber']);
        Route::get('/search-public', [App\Http\Controllers\Api\ShipmentTrackingController::class, 'searchByTrackingNumber']);
        Route::post('/validate-public', [App\Http\Controllers\Api\ShipmentTrackingController::class, 'validateTrackingNumber']);
        Route::get('/validate-public', [App\Http\Controllers\Api\ShipmentTrackingController::class, 'validateTrackingNumber']);
    });
});
