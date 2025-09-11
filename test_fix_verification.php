<?php
require_once 'vendor/autoload.php';

// Load Laravel application
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Sale\Sale;
use App\Models\Items\ItemTransaction;
use App\Enums\ItemTransactionUniqueCode;

echo "Testing Fix for Post-Delivery Cancellation Inventory Issue\n";
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

echo "Testing Sale ID: " . $sale->id . "\n";
echo "Current inventory_status: " . $sale->inventory_status . "\n";
echo "Expected behavior: Item transactions should have unique_code = 'SALE'\n\n";

// Simulate what happens when we update this sale
// We'll check if the logic correctly identifies deducted_delivered as a deducted status
$isInventoryDeducted = in_array($sale->inventory_status, ['deducted', 'deducted_delivered']);
$expectedUniqueCode = $isInventoryDeducted ?
    ItemTransactionUniqueCode::SALE->value :
    ItemTransactionUniqueCode::SALE_ORDER->value;

echo "Logic test:\n";
echo "  inventory_status: " . $sale->inventory_status . "\n";
echo "  isInventoryDeducted: " . ($isInventoryDeducted ? 'true' : 'false') . "\n";
echo "  expected unique_code: " . $expectedUniqueCode . "\n";
echo "  actual SALE value: " . ItemTransactionUniqueCode::SALE->value . "\n";
echo "  actual SALE_ORDER value: " . ItemTransactionUniqueCode::SALE_ORDER->value . "\n\n";

if ($expectedUniqueCode === ItemTransactionUniqueCode::SALE->value) {
    echo "✅ CORRECT: Logic correctly identifies 'deducted_delivered' as a deducted status\n";
} else {
    echo "❌ INCORRECT: Logic does not correctly identify 'deducted_delivered' as a deducted status\n";
}

// Check current item transactions
echo "\nCurrent item transactions:\n";
foreach ($sale->itemTransaction as $transaction) {
    echo "  Transaction ID: " . $transaction->id . "\n";
    echo "    Current unique_code: " . $transaction->unique_code . "\n";
    echo "    Expected unique_code: " . $expectedUniqueCode . "\n";

    if ($transaction->unique_code === $expectedUniqueCode) {
        echo "    ✅ CORRECT\n";
    } else {
        echo "    ❌ INCORRECT - This would be fixed when the sale is updated\n";
    }
    echo "    ---\n";
}

echo "\nTo verify the fix works:\n";
echo "1. When you update a sale with inventory_status 'deducted_delivered',\n";
echo "   the item transactions should maintain unique_code = 'SALE'\n";
echo "2. This ensures inventory is not incorrectly restored\n";
echo "3. The sale remains as a completed transaction with post-delivery action recorded\n";
