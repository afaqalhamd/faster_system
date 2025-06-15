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

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });  // <-- Missing semicolon here
    // Unit Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::apiResource('units', App\Http\Controllers\Api\UnitController::class);
    });
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
    // Inside the auth:sanctum middleware group
    Route::get('/sale-orders/{id}/convert', [App\Http\Controllers\Api\SaleOrderController::class, 'convertToSale']);
    Route::get('/sale-orders/{id}/details', [App\Http\Controllers\Api\SaleOrderController::class, 'details']);
    Route::get('/user-data', [AuthController::class, 'getUserData']);
// داخل مجموعة middleware auth:sanctum
Route::put('/items/{id}', [App\Http\Controllers\Api\ItemController::class, 'update']);
    // Item Routes
    Route::middleware('auth:sanctum')->group(function () {
        // Item Routes
        Route::get('/items', [App\Http\Controllers\Api\ItemController::class, 'index']);
        Route::get('/items/search', [App\Http\Controllers\Api\ItemController::class, 'search']);
        Route::get('/items/category/{categoryId}', [App\Http\Controllers\Api\ItemController::class, 'getItemsByCategory']);
        Route::get('/items/sku/{sku}', [App\Http\Controllers\Api\ItemController::class, 'getItemBySKU']);
        Route::get('/items/{id}', [App\Http\Controllers\Api\ItemController::class, 'show']);
    });

    // Currency Routes
    Route::middleware('auth:sanctum')->group(function () {
        // Currency Routes
        Route::get('/currencies', [App\Http\Controllers\Api\CurrencyController::class, 'index']);
        Route::get('/currencies/{id}', [App\Http\Controllers\Api\CurrencyController::class, 'show']);
        Route::get('/company-currency', [App\Http\Controllers\Api\CurrencyController::class, 'getCompanyCurrency']);
    });

    // Purchase Order Routes
    Route::apiResource('purchase-orders', App\Http\Controllers\Api\PurchaseOrderController::class);


// Item Transaction Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/item-transactions', [App\Http\Controllers\Api\ItemTransactionController::class, 'index']);
    Route::get('/item-transactions/{id}', [App\Http\Controllers\Api\ItemTransactionController::class, 'show']);
    Route::get('/item-transactions/item/{itemId}', [App\Http\Controllers\Api\ItemTransactionController::class, 'getTransactionsByItem']);
    Route::get('/item-transactions/warehouse/{warehouseId}', [App\Http\Controllers\Api\ItemTransactionController::class, 'getTransactionsByWarehouse']);
    Route::get('/recent-item-transactions', [App\Http\Controllers\Api\ItemTransactionController::class, 'getRecentTransactions']);
});
// Sale Transaction Report Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/reports/sales', [App\Http\Controllers\Api\SaleTransactionReportController::class, 'getAllSaleRecords']);
    Route::get('/reports/sales/items', [App\Http\Controllers\Api\SaleTransactionReportController::class, 'getAllSaleItemRecords']);
    Route::get('/reports/sales/payments', [App\Http\Controllers\Api\SaleTransactionReportController::class, 'getAllSalePaymentRecords']);
});
// Stock Report Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/reports/stock/moved-out', [App\Http\Controllers\Api\StockReportController::class, 'getProductsMovedOutLast24Hours']);
    Route::get('/reports/stock/out-of-stock', [App\Http\Controllers\Api\StockReportController::class, 'getOutOfStockProducts']);
    Route::get('/reports/stock/increased-quantity', [App\Http\Controllers\Api\StockReportController::class, 'getProductsWithIncreasedQuantity']);
});// Sale Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('sales', App\Http\Controllers\Api\SaleController::class);
});
// Item Transaction Report Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/reports/item-transactions/moved-in-24h', [App\Http\Controllers\Api\ItemTransactionReportController::class, 'getItemsMovedInLast24Hours']);
    Route::get('/reports/item-transactions/moved-out-24h', [App\Http\Controllers\Api\ItemTransactionReportController::class, 'getItemsMovedOutLast24Hours']);
});

// Device Token Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/device-tokens', [App\Http\Controllers\Api\DeviceTokenController::class, 'store']);
    Route::delete('/device-tokens', [App\Http\Controllers\Api\DeviceTokenController::class, 'destroy']);
});
Route::get('/send-notification', [PushNotificationController::class, 'sendPushNotification']);

// Notification Test Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/notifications/test', [App\Http\Controllers\Api\NotificationTestController::class, 'sendTestNotification']);
    Route::post('/notifications/test-all', [App\Http\Controllers\Api\NotificationTestController::class, 'sendTestNotificationToAllUsers']);
});
Route::get('/check-firebase', [App\Http\Controllers\PushNotificationController::class, 'checkFirebaseConnection']);
Route::post('/send-notification', [App\Http\Controllers\PushNotificationController::class, 'sendCustomNotification']);
Route::post('/notifications/send-custom', [App\Http\Controllers\PushNotificationController::class, 'sendCustomNotification']);

// Permission Groups Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('permission-groups', App\Http\Controllers\Api\UserPermissionsGroupController::class);
});