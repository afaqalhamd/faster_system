<?php
/**
 * Simple Test for POD Inventory Deduction
 * Access: http://your-domain/test-pod-deduction.php?sale_id=X
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

$saleId = $_GET['sale_id'] ?? 1;

try {
    echo "<h1>Testing POD Inventory Deduction</h1>";
    echo "<p>Testing Sale ID: {$saleId}</p>";

    // Find the sale
    $sale = Sale::with('itemTransaction')->findOrFail($saleId);
    echo "<p>✅ Sale found: {$sale->sale_code}</p>";
    echo "<p>Current Status: <strong>{$sale->sales_status}</strong></p>";
    echo "<p>Inventory Status: <strong>{$sale->inventory_status}</strong></p>";
    echo "<p>Items Count: {$sale->itemTransaction->count()}</p>";

    // Get the service
    $salesStatusService = app(SalesStatusService::class);

    // Check if transition is allowed
    $canTransition = $salesStatusService->canTransitionToStatus($sale, 'POD');
    echo "<p>Can transition to POD: " . ($canTransition ? "✅ Yes" : "❌ No") . "</p>";

    if (!$canTransition) {
        echo "<p style='color: red;'>❌ Cannot transition to POD from current status: {$sale->sales_status}</p>";
        echo "<p><strong>Solution:</strong> Change status to 'Completed' or 'Delivery' first, or update the transition rules.</p>";
        exit;
    }

    if ($sale->inventory_status === 'deducted') {
        echo "<p style='color: orange;'>⚠️ Inventory already deducted</p>";
        exit;
    }

    // Test the POD status update
    echo "<h2>Testing POD Status Update...</h2>";

    $result = $salesStatusService->updateSalesStatus($sale, 'POD', [
        'notes' => 'Test POD status update - ' . now()
    ]);

    if ($result['success']) {
        echo "<p style='color: green;'>✅ POD Status Update Successful!</p>";
        echo "<p>Message: {$result['message']}</p>";
        echo "<p>Inventory Updated: " . ($result['inventory_updated'] ? 'Yes' : 'No') . "</p>";

        // Refresh and show new status
        $sale->refresh();
        echo "<h3>Updated Sale Status:</h3>";
        echo "<p>Sales Status: <strong>{$sale->sales_status}</strong></p>";
        echo "<p>Inventory Status: <strong>{$sale->inventory_status}</strong></p>";
        echo "<p>Inventory Deducted At: {$sale->inventory_deducted_at}</p>";

    } else {
        echo "<p style='color: red;'>❌ POD Status Update Failed!</p>";
        echo "<p>Error: {$result['message']}</p>";
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Exception: {$e->getMessage()}</p>";
    echo "<pre>{$e->getTraceAsString()}</pre>";
}

echo "<hr>";
echo "<p><a href='?sale_id=" . ($saleId + 1) . "'>Test Next Sale</a> | ";
echo "<a href='/sale/invoice'>Back to Sales</a></p>";
?>
