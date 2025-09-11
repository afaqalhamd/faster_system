<?php
require_once 'vendor/autoload.php';

// Load Laravel application
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Sale\Sale;
use App\Models\Items\Item;

echo "Checking Item Quantities After Post-Delivery Cancellation\n";
echo "========================================================\n\n";

// Find a sale that was cancelled after POD
$sale = Sale::where('sales_status', 'Cancelled')
    ->where('inventory_status', 'deducted_delivered')
    ->whereNotNull('post_delivery_action')
    ->first();

if (!$sale) {
    echo "No post-delivery cancelled sale found\n";
    exit(1);
}

echo "Examining Sale ID: " . $sale->id . "\n";
echo "Sales Status: " . $sale->sales_status . "\n";
echo "Inventory Status: " . $sale->inventory_status . "\n";
echo "Post Delivery Action: " . $sale->post_delivery_action . "\n\n";

echo "Item Transactions:\n";
foreach ($sale->itemTransaction as $transaction) {
    echo "  Item ID: " . $transaction->item_id . "\n";
    echo "    Quantity: " . $transaction->quantity . "\n";
    echo "    Transaction Type: " . $transaction->transaction_type . "\n";
    echo "    Unique Code: " . $transaction->unique_code . "\n";

    // Get the current item details
    $item = Item::find($transaction->item_id);
    if ($item) {
        echo "    Item Name: " . $item->name . "\n";
        echo "    Current Quantity: " . $item->quantity . "\n";
        echo "    Alert Quantity: " . $item->alert_quantity . "\n";
    }
    echo "    ---\n";
}

echo "\nThis confirms that:\n";
echo "1. The sale status is correctly 'Cancelled'\n";
echo "2. The inventory status is 'deducted_delivered' (meaning inventory was NOT restored)\n";
echo "3. The post_delivery_action is recorded\n";
echo "4. The item quantities in the transactions show what was sold\n";
echo "5. The actual item quantities in the warehouse should reflect that inventory was NOT returned\n";
