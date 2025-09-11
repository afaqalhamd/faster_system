<?php
require_once 'vendor/autoload.php';

// Load Laravel application
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Sale\SaleOrder;
use App\Models\Sale\SaleOrderStatusHistory;

echo "Verifying Sale Order and Status History Relationships\n";
echo "====================================================\n\n";

// Get the first sale order
$saleOrder = SaleOrder::first();

if (!$saleOrder) {
    echo "❌ No sale orders found in database\n";
    exit(1);
}

echo "Found sale order (ID: {$saleOrder->id})\n";
echo "Current Status: {$saleOrder->order_status}\n";

// Test the relationship
$historiesCount = $saleOrder->saleOrderStatusHistories()->count();
echo "Status histories count: $historiesCount\n";

// Load with relationship
$saleOrderWithHistories = SaleOrder::with('saleOrderStatusHistories')->find($saleOrder->id);
echo "Status histories count (with relationship): {$saleOrderWithHistories->saleOrderStatusHistories->count()}\n";

if ($saleOrderWithHistories->saleOrderStatusHistories->count() > 0) {
    echo "\n✅ Relationship is working correctly!\n";
    echo "✅ Status histories are accessible through the relationship\n";

    echo "\nSample history record:\n";
    $firstHistory = $saleOrderWithHistories->saleOrderStatusHistories->first();
    echo "- Previous Status: " . ($firstHistory->previous_status ?? 'None') . "\n";
    echo "- New Status: {$firstHistory->new_status}\n";
    echo "- Changed At: {$firstHistory->changed_at}\n";
    echo "- Notes: " . ($firstHistory->notes ?? 'None') . "\n";
} else {
    echo "\n⚠️ No status histories found for this sale order\n";
    echo "The status history section will not be displayed on the edit page\n";
}

echo "\n✅ Verification completed!\n";
?>
