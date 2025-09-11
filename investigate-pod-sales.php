<?php
/**
 * Investigate Existing POD Sales
 * Check why existing POD sales show pending inventory
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
use App\Services\SalesStatusService;

echo "🔍 Investigating Existing POD Sales\n";
echo "==================================\n\n";

// Find all POD sales with pending inventory
$podSales = Sale::where('sales_status', 'POD')
    ->where('inventory_status', 'pending')
    ->with(['itemTransaction', 'party'])
    ->get();

echo "Found {$podSales->count()} POD sales with pending inventory:\n\n";

foreach ($podSales as $sale) {
    echo "Sale ID: {$sale->id}\n";
    echo "  Customer: " . $sale->party->getFullName() . "\n";
    echo "  Created: {$sale->created_at}\n";
    echo "  Status: {$sale->sales_status}\n";
    echo "  Inventory: {$sale->inventory_status}\n";
    echo "  Items: {$sale->itemTransaction->count()}\n";

    if ($sale->itemTransaction->count() > 0) {
        echo "  Item Transaction Codes:\n";
        foreach ($sale->itemTransaction as $transaction) {
            echo "    - Item {$transaction->item_id}: {$transaction->unique_code} (Qty: {$transaction->quantity})\n";
        }

        $saleOrderCount = $sale->itemTransaction->where('unique_code', ItemTransactionUniqueCode::SALE_ORDER->value)->count();
        $saleCount = $sale->itemTransaction->where('unique_code', ItemTransactionUniqueCode::SALE->value)->count();

        echo "  Summary: SALE_ORDER={$saleOrderCount}, SALE={$saleCount}\n";

        if ($saleOrderCount > 0 && $saleCount == 0) {
            echo "  ⚠️ ISSUE: POD status but items still have SALE_ORDER code (not deducted)\n";

            // Try to fix this sale
            echo "  🔧 Attempting to fix this sale...\n";

            $salesStatusService = app(SalesStatusService::class);

            try {
                // Force inventory deduction by calling the service again
                $result = $salesStatusService->updateSalesStatus($sale, 'POD', [
                    'notes' => 'Fix inventory deduction - ' . date('Y-m-d H:i:s')
                ]);

                if ($result['success']) {
                    $sale->refresh();
                    echo "  ✅ Fixed! New inventory status: {$sale->inventory_status}\n";
                } else {
                    echo "  ❌ Failed to fix: {$result['message']}\n";
                }
            } catch (Exception $e) {
                echo "  ❌ Exception: " . $e->getMessage() . "\n";
            }
        }
    }

    echo "\n";
}

// Check recent status changes
echo "Recent Status Changes Analysis:\n";
echo "==============================\n";

use App\Models\SalesStatusHistory;

$recentHistory = SalesStatusHistory::with(['sale', 'changedBy'])
    ->orderBy('changed_at', 'desc')
    ->limit(10)
    ->get();

foreach ($recentHistory as $history) {
    echo "Sale {$history->sale_id}: {$history->previous_status} → {$history->new_status}\n";
    echo "  Changed by: " . ($history->changedBy->name ?? 'Unknown') . "\n";
    echo "  At: {$history->changed_at}\n";
    echo "  Notes: " . ($history->notes ?? 'None') . "\n";
    echo "  Current inventory status: {$history->sale->inventory_status}\n\n";
}

echo "✅ Investigation Complete!\n";
