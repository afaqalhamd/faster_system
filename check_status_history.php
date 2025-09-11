<?php
require_once 'vendor/autoload.php';

// Load Laravel application
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Sale\SaleOrder;
use App\Models\Sale\SaleOrderStatusHistory;
use App\Services\PaymentTypeService;
use App\Services\AccountTransactionService;
use App\Services\ItemTransactionService;
use App\Services\Communication\Email\SaleOrderEmailNotificationService;
use App\Services\Communication\Sms\SaleOrderSmsNotificationService;
use App\Services\GeneralDataService;
use App\Services\StatusHistoryService;
use App\Services\SaleOrderStatusService;

echo "Checking Sale Order Status History Display Issue\n";
echo "==============================================\n\n";

// Check if there are any status history records
$totalHistories = SaleOrderStatusHistory::count();
echo "Total status history records in database: $totalHistories\n";

// Get a sale order that has status histories
$saleOrder = SaleOrder::with(['saleOrderStatusHistories', 'saleOrderStatusHistories.changedBy'])
    ->whereHas('saleOrderStatusHistories')
    ->first();

if (!$saleOrder) {
    echo "❌ No sale orders with status histories found\n";

    // Let's check if there are any sale orders at all
    $totalSaleOrders = SaleOrder::count();
    echo "Total sale orders in database: $totalSaleOrders\n";

    if ($totalSaleOrders > 0) {
        // Try to find any sale order and check its relationship
        $anySaleOrder = SaleOrder::with('saleOrderStatusHistories')->first();
        echo "Found a sale order (ID: {$anySaleOrder->id})\n";
        echo "Current Status: {$anySaleOrder->order_status}\n";
        $historiesCount = $anySaleOrder->saleOrderStatusHistories()->count();
        echo "Status histories count for this order: $historiesCount\n";

        if ($historiesCount > 0) {
            echo "\nStatus History Details:\n";
            $histories = $anySaleOrder->saleOrderStatusHistories;
            foreach ($histories as $history) {
                echo "- {$history->previous_status} → {$history->new_status} at {$history->changed_at}\n";
                echo "  Notes: " . ($history->notes ?? 'None') . "\n";
            }
        }
    }

    exit(1);
}

echo "✅ Found sale order with status histories\n";
echo "Sale Order ID: {$saleOrder->id}\n";
echo "Current Status: {$saleOrder->order_status}\n";
echo "Status Histories Count: {$saleOrder->saleOrderStatusHistories->count()}\n\n";

echo "Status History Details:\n";
foreach ($saleOrder->saleOrderStatusHistories as $history) {
    $userName = 'Unknown';
    if ($history->changedBy) {
        $userName = trim($history->changedBy->first_name . ' ' . $history->changedBy->last_name);
    } elseif ($history->changed_by) {
        $user = \App\Models\User::find($history->changed_by);
        $userName = $user ? trim($user->first_name . ' ' . $user->last_name) : 'Unknown User';
    }

    echo "- {$history->previous_status} → {$history->new_status} at {$history->changed_at}\n";
    echo "  Changed by: $userName\n";
    echo "  Notes: " . ($history->notes ?? 'None') . "\n\n";
}

echo "Checking if the edit page would display the status history:\n";
// Check if the order has status histories
if ($saleOrder->saleOrderStatusHistories->count() > 0) {
    echo "✅ The edit page should display the status history section\n";
    echo "✅ The view-status-history button should work correctly now\n";
} else {
    echo "❌ The edit page would not display the status history section\n";
}

echo "\n✅ Check completed!\n";
echo "\nTo test the fix:\n";
echo "1. Go to the sale order edit page for order ID: {$saleOrder->id}\n";
echo "2. Click the history button (🕒) next to the order status dropdown\n";
echo "3. The page should scroll to the status history section\n";
echo "4. The status history section should be visible\n";
?>
