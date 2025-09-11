<?php
/**
 * Fix Existing POD Sales with Incorrect Inventory Status
 * This will manually fix POD sales that should have deducted inventory
 */

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Models\Sale\Sale;
use App\Models\Items\ItemTransaction;
use App\Enums\ItemTransactionUniqueCode;
use App\Services\ItemTransactionService;
use Illuminate\Support\Facades\DB;

echo "🔧 Fixing Existing POD Sales\n";
echo "============================\n\n";

// Find POD sales with pending inventory (broken sales)
$brokenSales = Sale::where('sales_status', 'POD')
    ->where('inventory_status', 'pending')
    ->with('itemTransaction')
    ->get();

echo "Found {$brokenSales->count()} POD sales with pending inventory to fix:\n\n";

$itemTransactionService = app(ItemTransactionService::class);

foreach ($brokenSales as $sale) {
    echo "Fixing Sale ID: {$sale->id}\n";

    try {
        DB::beginTransaction();

        // Step 1: Update item transactions from SALE_ORDER to SALE
        $updatedTransactions = 0;
        foreach ($sale->itemTransaction as $transaction) {
            if ($transaction->unique_code === ItemTransactionUniqueCode::SALE_ORDER->value) {
                $transaction->update([
                    'unique_code' => ItemTransactionUniqueCode::SALE->value
                ]);
                $updatedTransactions++;
                echo "  ✅ Updated transaction {$transaction->id}: SALE_ORDER → SALE\n";

                // Update inventory quantities for this item
                $itemTransactionService->updateItemGeneralQuantityWarehouseWise($transaction->item_id);
                echo "  ✅ Updated inventory quantities for item {$transaction->item_id}\n";

                // Handle batch/serial tracking if applicable
                if ($transaction->tracking_type === 'batch') {
                    foreach ($transaction->itemBatchTransactions as $batchTransaction) {
                        $batchTransaction->update([
                            'unique_code' => ItemTransactionUniqueCode::SALE->value
                        ]);
                        $itemTransactionService->updateItemBatchQuantityWarehouseWise(
                            $batchTransaction->item_batch_master_id
                        );
                    }
                    echo "  ✅ Updated batch transactions\n";
                } elseif ($transaction->tracking_type === 'serial') {
                    foreach ($transaction->itemSerialTransaction as $serialTransaction) {
                        $serialTransaction->update([
                            'unique_code' => ItemTransactionUniqueCode::SALE->value
                        ]);
                        $itemTransactionService->updateItemSerialCurrentStatusWarehouseWise(
                            $serialTransaction->item_serial_master_id
                        );
                    }
                    echo "  ✅ Updated serial transactions\n";
                }
            }
        }

        // Step 2: Update sale inventory status
        $sale->update([
            'inventory_status' => 'deducted',
            'inventory_deducted_at' => now()
        ]);

        echo "  ✅ Updated sale inventory status to 'deducted'\n";
        echo "  📊 Transactions updated: {$updatedTransactions}\n";

        DB::commit();
        echo "  ✅ Sale {$sale->id} fixed successfully!\n\n";

    } catch (Exception $e) {
        DB::rollback();
        echo "  ❌ Failed to fix Sale {$sale->id}: " . $e->getMessage() . "\n\n";
    }
}

// Verification: Check if all POD sales now have correct inventory status
echo "Verification:\n";
echo "=============\n";

$allPodSales = Sale::where('sales_status', 'POD')->get();
$correctSales = $allPodSales->where('inventory_status', 'deducted')->count();
$totalPodSales = $allPodSales->count();

echo "Total POD sales: {$totalPodSales}\n";
echo "POD sales with deducted inventory: {$correctSales}\n";
echo "POD sales with pending inventory: " . ($totalPodSales - $correctSales) . "\n";

if ($correctSales === $totalPodSales) {
    echo "✅ All POD sales now have correct inventory status!\n";
} else {
    echo "⚠️ Some POD sales still have pending inventory status\n";
}

echo "\n🎉 Fix Complete!\n";
