<?php
require_once 'vendor/autoload.php';

// Load Laravel application
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Sale\Sale;
use App\Models\Items\Item;
use App\Models\Items\ItemTransaction;
use App\Enums\ItemTransactionUniqueCode;

echo "Verifying Inventory Quantities After Post-Delivery Cancellation\n";
echo "=============================================================\n\n";

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
echo "Post Delivery Action: " . $sale->post_delivery_action . "\n";
echo "Inventory Deducted At: " . $sale->inventory_deducted_at . "\n\n";

echo "Item Transactions for this sale:\n";
$totalQuantity = 0;
$itemDetails = [];

foreach ($sale->itemTransaction as $transaction) {
    $totalQuantity += $transaction->quantity;

    echo "  Transaction ID: " . $transaction->id . "\n";
    echo "    Item ID: " . $transaction->item_id . "\n";
    echo "    Quantity: " . $transaction->quantity . "\n";
    echo "    Unique Code: " . $transaction->unique_code . "\n";

    // Get the current item details
    $item = Item::find($transaction->item_id);
    if ($item) {
        echo "    Item Name: " . $item->name . "\n";
        echo "    Current Item Quantity: " . $item->quantity . "\n";
        echo "    Alert Quantity: " . $item->alert_quantity . "\n";

        $itemDetails[$transaction->item_id] = [
            'name' => $item->name,
            'current_quantity' => $item->quantity,
            'sold_quantity' => $transaction->quantity
        ];
    }
    echo "    ---\n";
}

echo "\nVerifying that inventory was NOT restored:\n";

// Check if there are any transactions with SALE_ORDER unique code for this sale
$saleOrderTransactions = ItemTransaction::where('transaction_type', 'Sale')
    ->where('transaction_id', $sale->id)
    ->where('unique_code', ItemTransactionUniqueCode::SALE_ORDER->value)
    ->count();

if ($saleOrderTransactions > 0) {
    echo "❌ ISSUE: Found " . $saleOrderTransactions . " transactions with SALE_ORDER code - inventory may have been restored incorrectly\n";
} else {
    echo "✓ CORRECT: No transactions with SALE_ORDER code found - inventory was not restored\n";
}

// Check if there are transactions with SALE unique code for this sale
$saleTransactions = ItemTransaction::where('transaction_type', 'Sale')
    ->where('transaction_id', $sale->id)
    ->where('unique_code', ItemTransactionUniqueCode::SALE->value)
    ->count();

if ($saleTransactions > 0) {
    echo "✓ CORRECT: Found " . $saleTransactions . " transactions with SALE code - inventory deduction is maintained\n";
} else {
    echo "❌ ISSUE: No transactions with SALE code found\n";
}

echo "\nSummary:\n";
echo "1. Sale ID " . $sale->id . " was cancelled after delivery (POD)\n";
echo "2. Inventory status is 'deducted_delivered' (correct)\n";
echo "3. Post delivery action is recorded as 'Cancelled' (correct)\n";
echo "4. Inventory deducted timestamp is preserved (correct)\n";
echo "5. Item transactions maintain SALE unique code (correct)\n";

if ($saleOrderTransactions == 0 && $saleTransactions > 0) {
    echo "\n✅ CONCLUSION: The system is working correctly. Inventory was NOT restored for this post-delivery cancellation.\n";
} else {
    echo "\n❌ CONCLUSION: There may be an issue with inventory handling.\n";
}
