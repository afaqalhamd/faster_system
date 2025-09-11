<?php
require_once 'vendor/autoload.php';

// Load Laravel application
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Sale\SaleOrder;
use App\Models\Sale\SaleOrderStatusHistory;

echo "Testing Sale Order Status History Database Access\n";
echo "===============================================\n\n";

// Get a sale order with status histories
$saleOrder = SaleOrder::with('saleOrderStatusHistories')->whereHas('saleOrderStatusHistories')->first();

if (!$saleOrder) {
    echo "❌ No sale orders with status histories found\n";
    
    // Let's check if there are any sale orders at all
    $totalSaleOrders = SaleOrder::count();
    echo "Total sale orders: $totalSaleOrders\n";
    
    // Let's check if there are any status history records at all
    $totalStatusHistories = SaleOrderStatusHistory::count();
    echo "Total status history records: $totalStatusHistories\n";
    
    exit(1);
}

echo "Found sale order with status histories (ID: {$saleOrder->id})\n";
echo "Number of status history records: " . $saleOrder->saleOrderStatusHistories->count() . "\n";

// Display the first status history record
if ($saleOrder->saleOrderStatusHistories->count() > 0) {
    $firstHistory = $saleOrder->saleOrderStatusHistories->first();
    echo "\nFirst status history record:\n";
    echo "ID: {$firstHistory->id}\n";
    echo "Sale Order ID: {$firstHistory->sale_order_id}\n";
    echo "Previous Status: {$firstHistory->previous_status}\n";
    echo "New Status: {$firstHistory->new_status}\n";
    echo "Changed At: {$firstHistory->changed_at}\n";
    echo "Created At: {$firstHistory->created_at}\n";
    echo "Updated At: {$firstHistory->updated_at}\n";
}

// Test the service method directly
try {
    $service = app()->make(\App\Services\SaleOrderStatusService::class);
    $history = $service->getStatusHistory($saleOrder);
    
    echo "\n✅ Service method working correctly!\n";
    echo "✅ Retrieved " . count($history) . " status history records\n";
} catch (Exception $e) {
    echo "\n❌ Error calling service method: " . $e->getMessage() . "\n";
}

echo "\n✅ Test completed!\n";