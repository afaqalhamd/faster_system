<?php
require_once 'vendor/autoload.php';

// Load Laravel application
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Sale\SaleOrder;
use Illuminate\Support\Facades\Route;

echo "Testing Sale Order Status History Route\n";
echo "=====================================\n\n";

// Get a sale order with status histories
$saleOrder = SaleOrder::with('saleOrderStatusHistories')->whereHas('saleOrderStatusHistories')->first();

if (!$saleOrder) {
    echo "❌ No sale orders with status histories found\n";
    exit(1);
}

echo "Found sale order with status histories (ID: {$saleOrder->id})\n";

// Test the route by simulating a request
try {
    // Use the app's URL generator to create the route
    $url = url("/sale/order/status-history/{$saleOrder->id}");
    echo "Route URL: $url\n";

    // Test with cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "HTTP Status Code: $httpCode\n";

    if ($httpCode === 200) {
        $data = json_decode($response, true);
        if ($data && isset($data['success']) && $data['success']) {
            echo "✅ Route is working correctly!\n";
            echo "✅ Status history data retrieved successfully\n";
            echo "Number of history records: " . count($data['data']) . "\n";
        } else {
            echo "❌ Route returned unexpected data format\n";
            echo "Response: $response\n";
        }
    } else {
        echo "❌ Route is not working. HTTP Code: $httpCode\n";
        echo "Response: $response\n";
    }
} catch (Exception $e) {
    echo "❌ Error testing route: " . $e->getMessage() . "\n";
}

echo "\n✅ Test completed!\n";
