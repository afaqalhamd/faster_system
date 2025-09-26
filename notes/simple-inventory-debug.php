<?php
/**
 * Simple Inventory Debug Script
 * Run: php simple-inventory-debug.php
 */

// Bootstrap Laravel
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Models\Sale\Sale;
use App\Services\SalesStatusService;
use App\Services\ItemTransactionService;

echo "🔍 Simple Inventory Debug\n";
echo "========================\n\n";

// Test 1: Service Instantiation
echo "1. Testing Service Instantiation\n";
try {
    $salesStatusService = app(SalesStatusService::class);
    echo "✅ SalesStatusService: OK\n";
} catch (Exception $e) {
    echo "❌ SalesStatusService failed: " . $e->getMessage() . "\n";
    exit(1);
}

try {
    $itemTransactionService = app(ItemTransactionService::class);
    echo "✅ ItemTransactionService: OK\n";
} catch (Exception $e) {
    echo "❌ ItemTransactionService failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Find a Recent Sale
echo "\n2. Finding Recent Sales\n";
$sales = Sale::with('itemTransaction')->orderBy('created_at', 'desc')->limit(3)->get();

if ($sales->count() == 0) {
    echo "❌ No sales found in system\n";
    exit(1);
}

echo "Found {$sales->count()} recent sales:\n";
foreach ($sales as $sale) {
    echo "  - ID: {$sale->id}, Status: {$sale->sales_status}, Inventory: {$sale->inventory_status}, Items: {$sale->itemTransaction->count()}\n";
}

// Test 3: Test Status Update on First Sale
$testSale = $sales->first();
echo "\n3. Testing Status Update on Sale ID: {$testSale->id}\n";

echo "Current Status: {$testSale->sales_status}\n";
echo "Inventory Status: {$testSale->inventory_status}\n";
echo "Items Count: {$testSale->itemTransaction->count()}\n";

// Check if we can transition to POD
$canTransition = false;
try {
    // Check using a reflection method since canTransitionToStatus might be private
    $reflection = new ReflectionClass($salesStatusService);
    if ($reflection->hasMethod('canTransitionToStatus')) {
        $method = $reflection->getMethod('canTransitionToStatus');
        $method->setAccessible(true);
        $canTransition = $method->invoke($salesStatusService, $testSale, 'POD');
    }
} catch (Exception $e) {
    echo "⚠️ Cannot check transition: " . $e->getMessage() . "\n";
}

echo "Can transition to POD: " . ($canTransition ? "✅ Yes" : "❌ No") . "\n";

// Test 4: Try Manual Status Update (if possible)
if ($canTransition && $testSale->inventory_status !== 'deducted') {
    echo "\n4. Testing Manual Status Update\n";

    try {
        $result = $salesStatusService->updateSalesStatus($testSale, 'POD', [
            'notes' => 'Test inventory update - ' . date('Y-m-d H:i:s')
        ]);

        if ($result['success']) {
            echo "✅ Status update successful\n";
            echo "   Message: {$result['message']}\n";
            echo "   Inventory Updated: " . ($result['inventory_updated'] ? 'Yes' : 'No') . "\n";

            // Refresh and check new status
            $testSale->refresh();
            echo "   New Sales Status: {$testSale->sales_status}\n";
            echo "   New Inventory Status: {$testSale->inventory_status}\n";
        } else {
            echo "❌ Status update failed: {$result['message']}\n";
        }

    } catch (Exception $e) {
        echo "❌ Exception during status update: " . $e->getMessage() . "\n";
        echo "   Stack trace:\n";
        echo "   " . substr($e->getTraceAsString(), 0, 500) . "...\n";
    }
} else {
    echo "\n4. Skipping Manual Test\n";
    echo "   Reason: ";
    if (!$canTransition) echo "Cannot transition to POD from {$testSale->sales_status}\n";
    if ($testSale->inventory_status === 'deducted') echo "Inventory already deducted\n";
}

// Test 5: Check ItemTransactionService method directly
echo "\n5. Testing ItemTransactionService Method\n";
if ($testSale->itemTransaction->count() > 0) {
    $firstItem = $testSale->itemTransaction->first();
    echo "Testing updateItemGeneralQuantityWarehouseWise for item ID: {$firstItem->item_id}\n";

    try {
        $result = $itemTransactionService->updateItemGeneralQuantityWarehouseWise($firstItem->item_id);
        echo "✅ updateItemGeneralQuantityWarehouseWise result: " . ($result ? 'true' : 'false') . "\n";
    } catch (Exception $e) {
        echo "❌ updateItemGeneralQuantityWarehouseWise failed: " . $e->getMessage() . "\n";
    }
}

echo "\n✅ Debug Complete!\n";
