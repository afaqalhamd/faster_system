<?php
/**
 * Fix Status History User Issues
 * Access: http://your-domain/fix-status-history-users.php
 */

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Models\SalesStatusHistory;
use App\Models\User;

?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix Status History Users</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .info { color: blue; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
        .section { margin: 20px 0; border: 1px solid #ddd; padding: 15px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>🔧 Fix Status History User Issues</h1>

    <div class="section">
        <h2>1. 📊 Current Status Analysis</h2>

        <?php
        try {
            $totalHistories = SalesStatusHistory::count();
            $historiesWithNullUser = SalesStatusHistory::whereNull('changed_by')->count();
            $historiesWithInvalidUser = SalesStatusHistory::whereNotNull('changed_by')
                ->whereNotExists(function($query) {
                    $query->select('id')
                          ->from('users')
                          ->whereColumn('users.id', 'sales_status_histories.changed_by');
                })->count();

            echo "<p><strong>Total Status History Records:</strong> {$totalHistories}</p>";
            echo "<p class='" . ($historiesWithNullUser > 0 ? 'warning' : 'success') . "'>";
            echo "<strong>Records with NULL changed_by:</strong> {$historiesWithNullUser}";
            echo "</p>";
            echo "<p class='" . ($historiesWithInvalidUser > 0 ? 'warning' : 'success') . "'>";
            echo "<strong>Records with Invalid User ID:</strong> {$historiesWithInvalidUser}";
            echo "</p>";

        } catch (Exception $e) {
            echo "<p class='error'>❌ Error analyzing data: " . $e->getMessage() . "</p>";
        }
        ?>
    </div>

    <div class="section">
        <h2>2. 👤 Available Users</h2>

        <?php
        try {
            $users = User::orderBy('created_at')->limit(10)->get();
            echo "<p><strong>Available Users:</strong></p>";
            echo "<ul>";
            foreach ($users as $user) {
                echo "<li>ID: {$user->id} - {$user->name} ({$user->email})</li>";
            }
            echo "</ul>";

            $firstUser = User::first();
            $adminUser = User::where('email', 'like', '%admin%')
                ->orWhere('name', 'like', '%admin%')
                ->first();

            echo "<p><strong>Default Users:</strong></p>";
            echo "<ul>";
            if ($firstUser) echo "<li>First User: {$firstUser->name} (ID: {$firstUser->id})</li>";
            if ($adminUser) echo "<li>Admin User: {$adminUser->name} (ID: {$adminUser->id})</li>";
            echo "</ul>";

        } catch (Exception $e) {
            echo "<p class='error'>❌ Error loading users: " . $e->getMessage() . "</p>";
        }
        ?>
    </div>

    <div class="section">
        <h2>3. 🔧 Fix Issues</h2>

        <?php
        if (isset($_POST['fix_issues'])) {
            try {
                $fixedCount = 0;

                // Get a default user to assign to problematic records
                $defaultUser = User::where('email', 'like', '%admin%')
                    ->orWhere('name', 'like', '%admin%')
                    ->first();

                if (!$defaultUser) {
                    $defaultUser = User::first();
                }

                if (!$defaultUser) {
                    echo "<p class='error'>❌ No users found in system. Cannot fix records.</p>";
                } else {
                    echo "<p class='info'>Using default user: {$defaultUser->name} (ID: {$defaultUser->id})</p>";

                    // Fix NULL changed_by records
                    $nullRecords = SalesStatusHistory::whereNull('changed_by')->get();
                    foreach ($nullRecords as $record) {
                        $record->update(['changed_by' => $defaultUser->id]);
                        $fixedCount++;
                    }

                    // Fix records with invalid user IDs
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
                echo "<p class='error'>❌ Error fixing issues: " . $e->getMessage() . "</p>";
            }
        } else {
            ?>
            <form method="POST">
                <input type="hidden" name="fix_issues" value="1">
                <button type="submit" style="background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">
                    🔧 Fix All User Issues
                </button>
            </form>
            <p class="warning">⚠️ This will assign problematic records to a default admin/first user.</p>
            <?php
        }
        ?>
    </div>

    <div class="section">
        <h2>4. 🧪 Test Status History Display</h2>

        <?php
        try {
            // Test loading status histories with users
            $histories = SalesStatusHistory::with('changedBy')->limit(5)->get();

            echo "<p><strong>Sample Status Histories:</strong></p>";
            if ($histories->count() > 0) {
                echo "<ul>";
                foreach ($histories as $history) {
                    $userName = $history->changedBy ? $history->changedBy->name : 'Unknown User';
                    echo "<li>Sale {$history->sale_id}: {$history->previous_status} → {$history->new_status} by {$userName}</li>";
                }
                echo "</ul>";
            } else {
                echo "<p class='warning'>No status histories found</p>";
            }

        } catch (Exception $e) {
            echo "<p class='error'>❌ Error testing display: " . $e->getMessage() . "</p>";
        }
        ?>
    </div>

    <div class="section">
        <h2>5. 🎯 Create Test Status History</h2>

        <?php
        if (isset($_POST['create_test'])) {
            try {
                $sale = \App\Models\Sale\Sale::first();
                if ($sale) {
                    $history = SalesStatusHistory::create([
                        'sale_id' => $sale->id,
                        'previous_status' => 'Test Previous',
                        'new_status' => 'Test New',
                        'notes' => 'Test record created at ' . now(),
                        'proof_image' => null,
                        'changed_at' => now(),
                        // Don't set changed_by - let the boot method handle it
                    ]);

                    $history->refresh();
                    $history->load('changedBy');

                    echo "<p class='success'>✅ Created test record: ID {$history->id}</p>";
                    echo "<p>Changed by: " . ($history->changedBy ? $history->changedBy->name : 'Still Unknown') . "</p>";
                } else {
                    echo "<p class='error'>❌ No sales found to create test record</p>";
                }
            } catch (Exception $e) {
                echo "<p class='error'>❌ Error creating test: " . $e->getMessage() . "</p>";
            }
        } else {
            ?>
            <form method="POST">
                <input type="hidden" name="create_test" value="1">
                <button type="submit" style="background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">
                    🧪 Create Test Status History
                </button>
            </form>
            <p class="info">This will test the boot() method automatic user assignment</p>
            <?php
        }
        ?>
    </div>

    <hr>
    <h2>📋 Summary</h2>
    <p>This script helps fix the "Changed by: Unknown" issue by:</p>
    <ol>
        <li>✅ Adding boot() method to SalesStatusHistory model for automatic user assignment</li>
        <li>✅ Fixing existing records with NULL or invalid changed_by values</li>
        <li>✅ Improving the SalesStatusService to handle missing users</li>
        <li>✅ Providing fallback mechanisms for system operations</li>
    </ol>

    <p><strong>Next Steps:</strong></p>
    <ul>
        <li>Run the fix to correct existing records</li>
        <li>Test creating new status changes</li>
        <li>Verify the edit page displays user names correctly</li>
    </ul>

</body>
</html>
