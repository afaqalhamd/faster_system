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
});

// Delivery API Routes
Route::prefix('delivery')->group(function () {
    // Delivery Authentication
    Route::post('/login', [App\Http\Controllers\Api\Delivery\AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        // Delivery Profile
        Route::get('/profile', [App\Http\Controllers\Api\Delivery\AuthController::class, 'profile']);
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
    });
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
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/item-transactions', [App\Http\Controllers\Api\ItemTransactionController::class, 'index']);
        Route::get('/item-transactions/{id}', [App\Http\Controllers\Api\ItemTransactionController::class, 'show']);
        Route::get('/item-transactions/item/{itemId}', [App\Http\Controllers\Api\ItemTransactionController::class, 'getTransactionsByItem']);
        Route::get('/item-transactions/warehouse/{warehouseId}', [App\Http\Controllers\Api\ItemTransactionController::class, 'getTransactionsByWarehouse']);
        Route::get('/recent-item-transactions', [App\Http\Controllers\Api\ItemTransactionController::class, 'getRecentTransactions']);
        Route::get('/items/{id}', [App\Http\Controllers\Api\ItemTransactionController::class, 'getItemTransactions']);
        Route::get('/{id}', [App\Http\Controllers\Api\ItemTransactionController::class, 'getTransactionDetails']);
    });

    // Sale Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::apiResource('sales', App\Http\Controllers\Api\SaleController::class);
    });

    // Sales API Routes - Complete CRUD and Operations
    Route::middleware('auth:sanctum')->group(function () {
        // Basic CRUD operations
        Route::get('/sales-api', [App\Http\Controllers\Api\SaleControllerApi::class, 'index']);
        Route::get('/sales-api/{id}', [App\Http\Controllers\Api\SaleControllerApi::class, 'show']);
        Route::post('/sales-api', [App\Http\Controllers\Api\SaleControllerApi::class, 'store']);
        Route::put('/sales-api/{id}', [App\Http\Controllers\Api\SaleControllerApi::class, 'update']);
        Route::delete('/sales-api/{id}', [App\Http\Controllers\Api\SaleControllerApi::class, 'destroy']);
        Route::apiResource('purchase-orders', App\Http\Controllers\Api\PurchaseOrderController::class);

        // Convert sale to return
        Route::post('/sales-api/{id}/convert-to-return', [App\Http\Controllers\Api\SaleControllerApi::class, 'convertToReturn']);

        // Email operations
        Route::post('/sales-api/{id}/send-email', [App\Http\Controllers\Api\SaleControllerApi::class, 'sendEmail']);
        Route::get('/sales-api/{id}/email-content', [App\Http\Controllers\Api\SaleControllerApi::class, 'getEmailContent']);

        // SMS operations
        Route::post('/sales-api/{id}/send-sms', [App\Http\Controllers\Api\SaleControllerApi::class, 'sendSms']);
        Route::get('/sales-api/{id}/sms-content', [App\Http\Controllers\Api\SaleControllerApi::class, 'getSmsContent']);

        // Get sold items data for returns
        Route::get('/sales-api/sold-items/{partyId}', [App\Http\Controllers\Api\SaleControllerApi::class, 'getSoldItemsData']);
        Route::get('/sales-api/sold-items/{partyId}/{itemId}', [App\Http\Controllers\Api\SaleControllerApi::class, 'getSoldItemsData']);
    });

    // Reports Routes
    Route::middleware('auth:sanctum')->group(function () {
        // Sale Transaction Report Routes
        Route::get('/reports/sales', [App\Http\Controllers\Api\SaleTransactionReportController::class, 'getAllSaleRecords']);
        Route::get('/reports/sales/items', [App\Http\Controllers\Api\SaleTransactionReportController::class, 'getAllSaleItemRecords']);
        Route::get('/reports/sales/payments', [App\Http\Controllers\Api\SaleTransactionReportController::class, 'getAllSalePaymentRecords']);

        // Stock Report Routes
        Route::get('/reports/stock/moved-out', [App\Http\Controllers\Api\StockReportController::class, 'getProductsMovedOutLast24Hours']);
        Route::get('/reports/stock/out-of-stock', [App\Http\Controllers\Api\StockReportController::class, 'getOutOfStockProducts']);
        Route::get('/reports/stock/increased-quantity', [App\Http\Controllers\Api\StockReportController::class, 'getProductsWithIncreasedQuantity']);

        // Item Transaction Report Routes
        Route::get('/reports/item-transactions/moved-in-24h', [App\Http\Controllers\Api\ItemTransactionReportController::class, 'getItemsMovedInLast24Hours']);
        Route::get('/reports/item-transactions/moved-out-24h', [App\Http\Controllers\Api\ItemTransactionReportController::class, 'getItemsMovedOutLast24Hours']);
    });

    // Device Token Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/device-tokens', [App\Http\Controllers\Api\DeviceTokenController::class, 'store']);
        Route::delete('/device-tokens', [App\Http\Controllers\Api\DeviceTokenController::class, 'destroy']);
    });

    // Notification Test Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/notifications/test', [App\Http\Controllers\Api\NotificationTestController::class, 'sendTestNotification']);
        Route::post('/notifications/test-all', [App\Http\Controllers\Api\NotificationTestController::class, 'sendTestNotificationToAllUsers']);
    });

    // Permission Groups Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::apiResource('permission-groups', App\Http\Controllers\Api\UserPermissionsGroupController::class);
    });
});

Route::get('/send-notification', [PushNotificationController::class, 'sendPushNotification']);
Route::get('/check-firebase', [App\Http\Controllers\PushNotificationController::class, 'checkFirebaseConnection']);
Route::post('/send-notification', [App\Http\Controllers\PushNotificationController::class, 'sendCustomNotification']);
Route::post('/notifications/send-custom', [App\Http\Controllers\PushNotificationController::class, 'sendCustomNotification']);
