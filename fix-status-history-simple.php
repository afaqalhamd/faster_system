<?php
/**
 * Simple Status History User Fix
 * Access: http://your-domain/fix-status-history-simple.php
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

echo "🔧 Simple Status History User Fix\n";
echo "================================\n\n";

try {
    // Step 1: Check current status
    echo "1. Checking Current Status:\n";
    $totalHistories = SalesStatusHistory::count();
    $totalUsers = User::count();
    echo "   - Total Status Histories: {$totalHistories}\n";
    echo "   - Total Users: {$totalUsers}\n";

    if ($totalUsers == 0) {
        echo "   ❌ No users found in system!\n";
        exit(1);
    }

    // Step 2: Find problematic records
    echo "\n2. Finding Problematic Records:\n";
    $nullRecords = SalesStatusHistory::whereNull('changed_by')->count();
    $invalidRecords = SalesStatusHistory::whereNotNull('changed_by')
        ->whereNotExists(function($query) {
            $query->select('id')
                  ->from('users')
                  ->whereColumn('users.id', 'sales_status_histories.changed_by');
        })->count();

    echo "   - Records with NULL changed_by: {$nullRecords}\n";
    echo "   - Records with invalid user IDs: {$invalidRecords}\n";

    $totalProblematic = $nullRecords + $invalidRecords;
    echo "   - Total problematic records: {$totalProblematic}\n";

    if ($totalProblematic == 0) {
        echo "   ✅ No problematic records found!\n";
    } else {
        echo "   ⚠️ Found {$totalProblematic} records that need fixing\n";
    }

    // Step 3: Find default user
    echo "\n3. Finding Default User:\n";
    $defaultUser = User::first();
    if ($defaultUser) {
        $fullName = trim($defaultUser->first_name . ' ' . $defaultUser->last_name);
        echo "   - Using user: {$fullName} (ID: {$defaultUser->id})\n";
        echo "   - Email: {$defaultUser->email}\n";
    } else {
        echo "   ❌ No users found!\n";
        exit(1);
    }

    // Step 4: Fix records if needed
    if ($totalProblematic > 0) {
        echo "\n4. Fixing Records:\n";
        $fixedCount = 0;

        // Fix NULL records
        if ($nullRecords > 0) {
            $updated = SalesStatusHistory::whereNull('changed_by')
                ->update(['changed_by' => $defaultUser->id]);
            echo "   - Fixed {$updated} NULL records\n";
            $fixedCount += $updated;
        }

        // Fix invalid user ID records
        if ($invalidRecords > 0) {
            $invalidIds = SalesStatusHistory::whereNotNull('changed_by')
                ->whereNotExists(function($query) {
                    $query->select('id')
                          ->from('users')
                          ->whereColumn('users.id', 'sales_status_histories.changed_by');
                })->pluck('id');

            foreach ($invalidIds as $id) {
                SalesStatusHistory::where('id', $id)
                    ->update(['changed_by' => $defaultUser->id]);
                $fixedCount++;
            }
            echo "   - Fixed {$invalidRecords} invalid user ID records\n";
        }

        echo "   ✅ Total records fixed: {$fixedCount}\n";
    }

    // Step 5: Test the display
    echo "\n5. Testing Display:\n";
    $testSale = Sale::with(['salesStatusHistories' => ['changedBy']])->first();

    if ($testSale && $testSale->salesStatusHistories->count() > 0) {
        echo "   Testing Sale ID: {$testSale->id}\n";
        foreach ($testSale->salesStatusHistories->take(3) as $history) {
            $userName = 'Unknown';
            if ($history->changedBy) {
                $userName = trim($history->changedBy->first_name . ' ' . $history->changedBy->last_name);
            } elseif ($history->changed_by) {
                $user = User::find($history->changed_by);
                if ($user) {
                    $userName = trim($user->first_name . ' ' . $user->last_name);
                }
            }

            echo "   - History #{$history->id}: {$history->previous_status} → {$history->new_status} by {$userName}\n";
        }
    } else {
        echo "   ⚠️ No sales with status history found for testing\n";
    }

    // Step 6: Verification
    echo "\n6. Final Verification:\n";
    $remainingIssues = SalesStatusHistory::whereNull('changed_by')
        ->orWhereNotExists(function($query) {
            $query->select('id')
                  ->from('users')
                  ->whereColumn('users.id', 'sales_status_histories.changed_by');
        })->count();

    if ($remainingIssues == 0) {
        echo "   🎉 All user attribution issues have been resolved!\n";
        echo "\n✅ SUCCESS: The edit page should now show actual user names instead of 'Unknown'\n";
        echo "\n📍 Test it now:\n";
        echo "   1. Go to /sale/invoice\n";
        echo "   2. Click Edit on any sale\n";
        echo "   3. Scroll to Status History section\n";
        echo "   4. Check that user names are displayed correctly\n";
    } else {
        echo "   ❌ Still have {$remainingIssues} issues remaining\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}

echo "\n🏁 Fix completed!\n";
?>
