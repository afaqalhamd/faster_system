<?php
require_once 'vendor/autoload.php';

// Load Laravel application
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Sale\SaleOrder;
use App\Models\Sale\SaleOrderStatusHistory;

echo "Simple Sale Order Status History Check\n";
echo "=====================================\n\n";

// Check if there are any status history records
$totalHistories = SaleOrderStatusHistory::count();
echo "Total status history records in database: $totalHistories\n";

// Get the first sale order
$saleOrder = SaleOrder::first();
if ($saleOrder) {
    echo "Found a sale order (ID: {$saleOrder->id})\n";
    echo "Current Status: {$saleOrder->order_status}\n";

    // Check if this sale order has status histories
    $historiesCount = $saleOrder->saleOrderStatusHistories()->count();
    echo "Status histories count for this order: $historiesCount\n";

    if ($historiesCount > 0) {
        echo "\nStatus History Details:\n";
        $histories = $saleOrder->saleOrderStatusHistories;
        foreach ($histories as $history) {
            echo "- {$history->previous_status} → {$history->new_status} at {$history->changed_at}\n";
            echo "  Notes: " . ($history->notes ?? 'None') . "\n";
        }
    }
} else {
    echo "❌ No sale orders found in database\n";
}

echo "\n✅ Check completed!\n";
?>
