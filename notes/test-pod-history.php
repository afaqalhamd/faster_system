<?php
/**
 * Test POD Status History Fix
 * Add this route to web.php temporarily for testing:
 * Route::get('/test-pod-history/{id}', function($id) { include base_path('test-pod-history.php'); });
 */

use App\Models\Sale\Sale;
use App\Services\SalesStatusService;
use App\Models\SalesStatusHistory;

echo "<h1>Testing POD Status History</h1>";

$saleId = (int) $id;

try {
    // Test 1: Find the sale
    $sale = Sale::findOrFail($saleId);
    echo "<p>✅ Sale found: ID {$sale->id}, Status: {$sale->sales_status}</p>";

    // Test 2: Check if status histories exist
    $histories = SalesStatusHistory::where('sale_id', $saleId)->get();
    echo "<p>📊 Found " . $histories->count() . " status history records</p>";

    // Test 3: Test the service method
    $salesStatusService = app(SalesStatusService::class);
    $history = $salesStatusService->getStatusHistory($sale);

    echo "<h2>Status History Data:</h2>";
    echo "<pre>" . json_encode($history, JSON_PRETTY_PRINT) . "</pre>";

    // Test 4: Create a test history record if none exist
    if (empty($history)) {
        echo "<p>⚠️ No history found. Creating test record...</p>";

        SalesStatusHistory::create([
            'sale_id' => $sale->id,
            'previous_status' => 'Pending',
            'new_status' => 'Processing',
            'notes' => 'Test status change',
            'proof_image' => null,
            'changed_by' => auth()->id() ?? 1, // Use auth user or default to user ID 1
            'changed_at' => now(),
        ]);

        echo "<p>✅ Test history record created</p>";

        // Re-fetch history
        $history = $salesStatusService->getStatusHistory($sale);
        echo "<h2>Updated Status History Data:</h2>";
        echo "<pre>" . json_encode($history, JSON_PRETTY_PRINT) . "</pre>";
    }

} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>File: " . $e->getFile() . " Line: " . $e->getLine() . "</p>";
}

echo "<hr>";
echo "<p><a href='/sale/invoice'>← Back to Sales</a></p>";
?>
