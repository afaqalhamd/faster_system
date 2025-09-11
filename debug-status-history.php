<?php
/**
 * Debug Status History Issues
 * Run this by accessing: http://your-domain/debug-status-history.php
 */

// Bootstrap Laravel
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Models\Sale\Sale;
use App\Models\SalesStatusHistory;
use App\Models\User;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Status History</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Debug Status History Issues</h1>

    <?php
    try {
        // Test 1: Check if tables exist
        echo "<h2>1. Database Tables Check</h2>";

        $salesCount = Sale::count();
        echo "<p class='success'>✅ Sales table: {$salesCount} records</p>";

        $historyCount = SalesStatusHistory::count();
        echo "<p class='success'>✅ Sales Status History table: {$historyCount} records</p>";

        $usersCount = User::count();
        echo "<p class='success'>✅ Users table: {$usersCount} records</p>";

        // Test 2: Check relationships
        echo "<h2>2. Relationships Check</h2>";

        $sale = Sale::first();
        if ($sale) {
            echo "<p class='success'>✅ Found sale: ID {$sale->id}</p>";

            // Check if relationship works
            $histories = $sale->salesStatusHistories()->with('changedBy')->get();
            echo "<p class='info'>📊 Sale has " . $histories->count() . " status history records</p>";

            foreach ($histories as $history) {
                $userName = $history->changedBy ? $history->changedBy->name : 'Unknown User';
                echo "<p>- Status: {$history->previous_status} → {$history->new_status}, Changed by: {$userName}</p>";
            }
        } else {
            echo "<p class='error'>❌ No sales found</p>";
        }

        // Test 3: Test the service directly
        echo "<h2>3. Service Test</h2>";

        if ($sale) {
            $salesStatusService = app(\App\Services\SalesStatusService::class);
            $history = $salesStatusService->getStatusHistory($sale);

            echo "<p class='info'>Service returned " . count($history) . " records</p>";
            echo "<pre>" . json_encode($history, JSON_PRETTY_PRINT) . "</pre>";
        }

        // Test 4: Create a test record
        echo "<h2>4. Create Test Record</h2>";

        if ($sale && $usersCount > 0) {
            $user = User::first();

            // Create test history record
            $testHistory = SalesStatusHistory::create([
                'sale_id' => $sale->id,
                'previous_status' => 'Test Previous',
                'new_status' => 'Test New',
                'notes' => 'Debug test record created at ' . now(),
                'proof_image' => null,
                'changed_by' => $user->id,
                'changed_at' => now(),
            ]);

            echo "<p class='success'>✅ Created test history record: ID {$testHistory->id}</p>";

            // Verify the record with relationship
            $testWithUser = SalesStatusHistory::with('changedBy')->find($testHistory->id);
            $testUserName = $testWithUser->changedBy ? $testWithUser->changedBy->name : 'Still Unknown';
            echo "<p>Test record user: {$testUserName}</p>";
        }

    } catch (Exception $e) {
        echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
        echo "<p>File: " . $e->getFile() . " Line: " . $e->getLine() . "</p>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
    ?>

    <hr>
    <p><strong>If you see errors above, here are the fixes:</strong></p>
    <ol>
        <li>Run: <code>php artisan migrate</code></li>
        <li>Check if User model exists and has records</li>
        <li>Verify foreign key constraints</li>
        <li>Check Laravel log files for detailed errors</li>
    </ol>
</body>
</html>
