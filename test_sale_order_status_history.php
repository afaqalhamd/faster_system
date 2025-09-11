<?php
// Test script to verify sale order status history functionality
require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\Route;

// Test if the route exists
echo "Testing Sale Order Status History Route...\n";

// Check if the route is defined
$routeExists = false;
try {
    $route = app('router')->getRoutes()->getByName('sale.order.status.history');
    if ($route) {
        $routeExists = true;
        echo "✅ Route 'sale.order.status.history' exists\n";
        echo "   URL: " . $route->uri() . "\n";
    } else {
        echo "❌ Route 'sale.order.status.history' does not exist\n";
    }
} catch (Exception $e) {
    echo "❌ Error checking route: " . $e->getMessage() . "\n";
}

// Test if the controller method exists
echo "\nTesting Controller Method...\n";
try {
    $controller = new \App\Http\Controllers\Sale\SaleOrderController(
        new \App\Services\PaymentTypeService(),
        new \App\Services\PaymentTransactionService(),
        new \App\Services\AccountTransactionService(),
        new \App\Services\ItemTransactionService(),
        new \App\Services\Communication\Email\SaleOrderEmailNotificationService(),
        new \App\Services\Communication\Sms\SaleOrderSmsNotificationService(),
        new \App\Services\GeneralDataService(),
        new \App\Services\StatusHistoryService(),
        new \App\Services\SaleOrderStatusService(new \App\Services\ItemTransactionService())
    );

    if (method_exists($controller, 'getStatusHistory')) {
        echo "✅ Controller method 'getStatusHistory' exists\n";
    } else {
        echo "❌ Controller method 'getStatusHistory' does not exist\n";
    }

    if (method_exists($controller, 'updateStatus')) {
        echo "✅ Controller method 'updateStatus' exists\n";
    } else {
        echo "❌ Controller method 'updateStatus' does not exist\n";
    }
} catch (Exception $e) {
    echo "❌ Error testing controller: " . $e->getMessage() . "\n";
}

echo "\nTest completed.\n";
?>
