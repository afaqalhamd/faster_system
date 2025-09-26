<?php
/**
 * Test Status History Display
 */

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Models\Sale\Sale;
use App\Models\SalesStatusHistory;

echo "🧪 Testing Status History Display\n";
echo "=================================\n\n";

try {
    // Find a sale to test with
    $sale = Sale::first();
    if (!$sale) {
        echo "❌ No sales found in database\n";
        exit(1);
    }

    echo "Found sale ID: {$sale->id}\n";

    // Check existing histories
    $existingHistory = SalesStatusHistory::where('sale_id', $sale->id)->count();
    echo "Existing histories for this sale: {$existingHistory}\n";

    // Create test status history if none exists
    if ($existingHistory == 0) {
        echo "Creating test status history...\n";
        $history = SalesStatusHistory::create([
            'sale_id' => $sale->id,
            'previous_status' => 'Pending',
            'new_status' => 'Processing',
            'notes' => 'Test status change created at ' . now(),
            'proof_image' => null,
            'changed_by' => 1, // Using first user
            'changed_at' => now(),
        ]);
        echo "✅ Created test status history: ID {$history->id}\n";
    }

    // Test loading with relationship
    echo "\nTesting relationship loading:\n";
    $saleWithHistory = Sale::with(['salesStatusHistories' => ['changedBy']])->find($sale->id);

    if ($saleWithHistory->salesStatusHistories->count() > 0) {
        foreach ($saleWithHistory->salesStatusHistories as $history) {
            $userName = 'Unknown';
            if ($history->changedBy) {
                $userName = trim($history->changedBy->first_name . ' ' . $history->changedBy->last_name);
            }
            echo "  - History #{$history->id}: {$history->previous_status} → {$history->new_status} by {$userName}\n";
        }

        echo "\n✅ Status history is working correctly!\n";
        echo "\n📍 Now test in browser:\n";
        echo "   Go to: /sale/invoice/{$sale->id}/edit\n";
        echo "   Scroll to Status History section\n";
        echo "   You should see actual user names instead of 'Unknown'\n";
    } else {
        echo "⚠️ No status histories found even after creation\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n🏁 Test completed!\n";
?>
