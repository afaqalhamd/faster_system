<?php
/**
 * Comprehensive Status History User Fix
 * Access: http://your-domain/comprehensive-status-history-fix.php
 */

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Models\SalesStatusHistory;
use App\Models\User;
use App\Models\Sale\Sale;

?>
<!DOCTYPE html>
<html>
<head>
    <title>Comprehensive Status History User Fix</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .info { color: blue; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
        .section { margin: 20px 0; border: 1px solid #ddd; padding: 15px; border-radius: 5px; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .btn { padding: 10px 15px; margin: 5px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-primary { background: #007cba; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-warning { background: #ffc107; color: black; }
    </style>
</head>
<body>
    <h1>🔧 Comprehensive Status History User Fix</h1>

    <div class="section">
        <h2>1. 📊 Current Database Analysis</h2>

        <?php
        try {
            // Check users table
            $totalUsers = User::count();
            $firstUser = User::first();
            $adminUser = User::where('email', 'like', '%admin%')
                ->orWhere('name', 'like', '%admin%')
                ->first();

            echo "<p><strong>Users Analysis:</strong></p>";
            echo "<table>";
            echo "<tr><th>Metric</th><th>Value</th></tr>";
            echo "<tr><td>Total Users</td><td>{$totalUsers}</td></tr>";
            echo "<tr><td>First User</td><td>" . ($firstUser ? "{$firstUser->name} (ID: {$firstUser->id})" : "None") . "</td></tr>";
            echo "<tr><td>Admin User</td><td>" . ($adminUser ? "{$adminUser->name} (ID: {$adminUser->id})" : "None") . "</td></tr>";
            echo "</table>";

            // Check status histories
            $totalHistories = SalesStatusHistory::count();
            $historiesWithNullUser = SalesStatusHistory::whereNull('changed_by')->count();
            $historiesWithInvalidUser = SalesStatusHistory::whereNotNull('changed_by')
                ->whereNotExists(function($query) {
                    $query->select('id')
                          ->from('users')
                          ->whereColumn('users.id', 'sales_status_histories.changed_by');
                })->count();

            echo "<p><strong>Status Histories Analysis:</strong></p>";
            echo "<table>";
            echo "<tr><th>Metric</th><th>Value</th><th>Status</th></tr>";
            echo "<tr><td>Total Records</td><td>{$totalHistories}</td><td class='" . ($totalHistories > 0 ? 'success' : 'warning') . "'>" . ($totalHistories > 0 ? '✅' : '⚠️') . "</td></tr>";
            echo "<tr><td>NULL changed_by</td><td>{$historiesWithNullUser}</td><td class='" . ($historiesWithNullUser > 0 ? 'warning' : 'success') . "'>" . ($historiesWithNullUser > 0 ? '⚠️' : '✅') . "</td></tr>";
            echo "<tr><td>Invalid User IDs</td><td>{$historiesWithInvalidUser}</td><td class='" . ($historiesWithInvalidUser > 0 ? 'error' : 'success') . "'>" . ($historiesWithInvalidUser > 0 ? '❌' : '✅') . "</td></tr>";
            echo "</table>";

        } catch (Exception $e) {
            echo "<p class='error'>❌ Error analyzing database: " . $e->getMessage() . "</p>";
        }
        ?>
    </div>

    <div class="section">
        <h2>2. 🔍 Detailed Problem Records</h2>

        <?php
        try {
            // Show problematic records
            $problematicRecords = SalesStatusHistory::whereNull('changed_by')
                ->orWhereNotExists(function($query) {
                    $query->select('id')
                          ->from('users')
                          ->whereColumn('users.id', 'sales_status_histories.changed_by');
                })
                ->with(['sale'])
                ->limit(10)
                ->get();

            if ($problematicRecords->count() > 0) {
                echo "<p class='warning'>⚠️ Found " . $problematicRecords->count() . " problematic records (showing first 10):</p>";
                echo "<table>";
                echo "<tr><th>History ID</th><th>Sale ID</th><th>Status Change</th><th>changed_by Value</th><th>Issue</th></tr>";

                foreach ($problematicRecords as $record) {
                    $issue = is_null($record->changed_by) ? 'NULL Value' : 'Invalid User ID';
                    echo "<tr>";
                    echo "<td>{$record->id}</td>";
                    echo "<td>{$record->sale_id}</td>";
                    echo "<td>{$record->previous_status} → {$record->new_status}</td>";
                    echo "<td>" . ($record->changed_by ?? 'NULL') . "</td>";
                    echo "<td class='" . ($issue == 'NULL Value' ? 'warning' : 'error') . "'>{$issue}</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p class='success'>✅ No problematic records found!</p>";
            }

        } catch (Exception $e) {
            echo "<p class='error'>❌ Error checking records: " . $e->getMessage() . "</p>";
        }
        ?>
    </div>

    <div class="section">
        <h2>3. 🧪 Test Current Display</h2>

        <?php
        try {
            // Test how the edit page would display
            $testSale = Sale::with(['salesStatusHistories' => ['changedBy']])->first();

            if ($testSale && $testSale->salesStatusHistories->count() > 0) {
                echo "<p><strong>Testing Sale ID {$testSale->id} Status History Display:</strong></p>";
                echo "<table>";
                echo "<tr><th>History ID</th><th>Status Change</th><th>changedBy Relationship</th><th>Display Result</th></tr>";

                foreach ($testSale->salesStatusHistories->take(5) as $history) {
                    $displayName = $history->changedBy->name ?? 'Unknown';
                    $relationshipStatus = $history->changedBy ? 'Loaded' : 'NULL';

                    echo "<tr>";
                    echo "<td>{$history->id}</td>";
                    echo "<td>{$history->previous_status} → {$history->new_status}</td>";
                    echo "<td class='" . ($history->changedBy ? 'success' : 'error') . "'>{$relationshipStatus}</td>";
                    echo "<td class='" . ($displayName !== 'Unknown' ? 'success' : 'error') . "'>{$displayName}</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p class='warning'>⚠️ No sales with status history found for testing</p>";
            }

        } catch (Exception $e) {
            echo "<p class='error'>❌ Error testing display: " . $e->getMessage() . "</p>";
        }
        ?>
    </div>

    <div class="section">
        <h2>4. 🔧 Automatic Fix</h2>

        <?php
        if (isset($_POST['fix_all'])) {
            try {
                $fixedCount = 0;
                $defaultUser = null;

                // Find best default user
                $adminUser = User::where('email', 'like', '%admin%')
                    ->orWhere('name', 'like', '%admin%')
                    ->first();

                if ($adminUser) {
                    $defaultUser = $adminUser;
                } else {
                    $defaultUser = User::first();
                }

                if (!$defaultUser) {
                    echo "<p class='error'>❌ No users found in system. Cannot fix records.</p>";
                } else {
                    echo "<p class='info'>ℹ️ Using default user: {$defaultUser->name} (ID: {$defaultUser->id})</p>";

                    // Fix NULL changed_by records
                    $nullRecords = SalesStatusHistory::whereNull('changed_by')->get();
                    foreach ($nullRecords as $record) {
                        $record->update(['changed_by' => $defaultUser->id]);
                        $fixedCount++;
                    }

                    // Fix invalid user ID records
                    $invalidRecords = SalesStatusHistory::whereNotNull('changed_by')
                        ->whereNotExists(function($query) {
                            $query->select('id')
                                  ->from('users')
                                  ->whereColumn('users.id', 'sales_status_histories.changed_by');
                        })->get();

                    foreach ($invalidRecords as $record) {
                        $record->update(['changed_by' => $defaultUser->id]);
                        $fixedCount++;
                    }

                    echo "<p class='success'>✅ Fixed {$fixedCount} status history records</p>";
                }

            } catch (Exception $e) {
                echo "<p class='error'>❌ Error during fix: " . $e->getMessage() . "</p>";
            }
        } else {
            ?>
            <form method="POST">
                <input type="hidden" name="fix_all" value="1">
                <button type="submit" class="btn btn-success">
                    🔧 Fix All User Attribution Issues
                </button>
            </form>
            <p class="warning">⚠️ This will assign all problematic records to a default admin/first user.</p>
            <?php
        }
        ?>
    </div>

    <div class="section">
        <h2>5. 🎯 Create Test Record</h2>

        <?php
        if (isset($_POST['create_test'])) {
            try {
                $sale = Sale::first();
                if ($sale) {
                    // Create a test record to verify the boot method works
                    $history = SalesStatusHistory::create([
                        'sale_id' => $sale->id,
                        'previous_status' => 'Test Previous',
                        'new_status' => 'Test New',
                        'notes' => 'Test record created at ' . now() . ' - Should auto-assign user',
                        'proof_image' => null,
                        'changed_at' => now(),
                        // Don't set changed_by - let boot method handle it
                    ]);

                    // Reload with relationship
                    $history = SalesStatusHistory::with('changedBy')->find($history->id);

                    echo "<p class='success'>✅ Created test record: ID {$history->id}</p>";
                    echo "<p>Changed by: " . ($history->changedBy ? $history->changedBy->name : 'Still Unknown') . " (ID: " . ($history->changed_by ?? 'NULL') . ")</p>";

                    if ($history->changedBy) {
                        echo "<p class='success'>✅ Boot method is working correctly!</p>";
                    } else {
                        echo "<p class='warning'>⚠️ Boot method may need authentication context</p>";
                    }
                } else {
                    echo "<p class='error'>❌ No sales found to create test record</p>";
                }
            } catch (Exception $e) {
                echo "<p class='error'>❌ Error creating test: " . $e->getMessage() . "</p>";
                echo "<pre>" . $e->getTraceAsString() . "</pre>";
            }
        } else {
            ?>
            <form method="POST">
                <input type="hidden" name="create_test" value="1">
                <button type="submit" class="btn btn-primary">
                    🧪 Create Test Status History Record
                </button>
            </form>
            <p class="info">This tests if the boot() method automatically assigns users</p>
            <?php
        }
        ?>
    </div>

    <div class="section">
        <h2>6. ✅ Verification</h2>

        <?php
        if (isset($_POST['verify'])) {
            try {
                // Re-test the display after fixes
                $testSale = Sale::with(['salesStatusHistories' => ['changedBy']])->first();

                if ($testSale && $testSale->salesStatusHistories->count() > 0) {
                    echo "<p><strong>Post-Fix Verification for Sale ID {$testSale->id}:</strong></p>";
                    echo "<table>";
                    echo "<tr><th>History ID</th><th>Status Change</th><th>User Name</th><th>Status</th></tr>";

                    $allGood = true;
                    foreach ($testSale->salesStatusHistories->take(5) as $history) {
                        $displayName = $history->changedBy->name ?? 'Unknown';
                        $isGood = $displayName !== 'Unknown';
                        if (!$isGood) $allGood = false;

                        echo "<tr>";
                        echo "<td>{$history->id}</td>";
                        echo "<td>{$history->previous_status} → {$history->new_status}</td>";
                        echo "<td class='" . ($isGood ? 'success' : 'error') . "'>{$displayName}</td>";
                        echo "<td>" . ($isGood ? '✅' : '❌') . "</td>";
                        echo "</tr>";
                    }
                    echo "</table>";

                    if ($allGood) {
                        echo "<p class='success'>🎉 All status history records now display user names correctly!</p>";
                    } else {
                        echo "<p class='error'>❌ Some records still show 'Unknown'. May need manual investigation.</p>";
                    }
                }

                // Check if any issues remain
                $remainingIssues = SalesStatusHistory::whereNull('changed_by')
                    ->orWhereNotExists(function($query) {
                        $query->select('id')
                              ->from('users')
                              ->whereColumn('users.id', 'sales_status_histories.changed_by');
                    })->count();

                echo "<p><strong>Remaining Issues:</strong> {$remainingIssues}</p>";
                if ($remainingIssues == 0) {
                    echo "<p class='success'>🎉 All user attribution issues have been resolved!</p>";
                }

            } catch (Exception $e) {
                echo "<p class='error'>❌ Error during verification: " . $e->getMessage() . "</p>";
            }
        } else {
            ?>
            <form method="POST">
                <input type="hidden" name="verify" value="1">
                <button type="submit" class="btn btn-warning">
                    ✅ Verify Fix Results
                </button>
            </form>
            <p class="info">Run this after applying fixes to verify the results</p>
            <?php
        }
        ?>
    </div>

    <hr>
    <h2>📋 Quick Action Summary</h2>
    <ol>
        <li><strong>Run Analysis:</strong> Check the current status above</li>
        <li><strong>Apply Fix:</strong> Click "Fix All User Attribution Issues"</li>
        <li><strong>Verify Results:</strong> Click "Verify Fix Results"</li>
        <li><strong>Test Edit Page:</strong> Go to a sale edit page and check status history</li>
    </ol>

    <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin-top: 20px;">
        <h3>🎯 Expected Result</h3>
        <p>After running the fix, the edit page should show actual user names instead of "Changed by: Unknown"</p>
        <p><strong>Test URL:</strong> <a href="/sale/invoice" target="_blank">Go to Sales → Edit any sale → Scroll to Status History</a></p>
    </div>

</body>
</html>
