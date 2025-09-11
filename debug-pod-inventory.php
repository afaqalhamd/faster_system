<?php
/**
 * Debug POD Inventory Deduction Issue
 * Run this by accessing: http://your-domain/debug-pod-inventory.php?sale_id=X
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
use App\Models\Items\ItemTransaction;
use App\Enums\ItemTransactionUniqueCode;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug POD Inventory Deduction</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .info { color: blue; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>🔍 Debug POD Inventory Deduction Issue</h1>

    <?php
    $saleId = $_GET['sale_id'] ?? null;

    if (!$saleId) {
        echo "<p class='error'>❌ Please provide sale_id parameter: ?sale_id=X</p>";
        echo "<p>Example: <a href='?sale_id=1'>debug-pod-inventory.php?sale_id=1</a></p>";
        exit;
    }

    try {
        echo "<h2>📊 Sale Information</h2>";
        $sale = Sale::with(['itemTransaction', 'party'])->findOrFail($saleId);

        echo "<table>";
        echo "<tr><th>Property</th><th>Value</th></tr>";
        echo "<tr><td>Sale ID</td><td>{$sale->id}</td></tr>";
        echo "<tr><td>Current Status</td><td><strong>{$sale->sales_status}</strong></td></tr>";
        echo "<tr><td>Inventory Status</td><td><strong>{$sale->inventory_status}</strong></td></tr>";
        echo "<tr><td>Inventory Deducted At</td><td>{$sale->inventory_deducted_at}</td></tr>";
        echo "<tr><td>Customer</td><td>{$sale->party->getFullName()}</td></tr>";
        echo "<tr><td>Grand Total</td><td>{$sale->grand_total}</td></tr>";
        echo "<tr><td>Item Transactions Count</td><td>{$sale->itemTransaction->count()}</td></tr>";
        echo "</table>";

        echo "<h2>🎯 Status Transition Analysis</h2>";
        $salesStatusService = app(SalesStatusService::class);

        // Check if POD is allowed from current status
        $currentStatus = $sale->sales_status;
        $canTransitionToPOD = $salesStatusService->canTransitionToStatus($sale, 'POD');

        echo "<p><strong>Current Status:</strong> {$currentStatus}</p>";
        echo "<p><strong>Can transition to POD:</strong> " . ($canTransitionToPOD ? "<span class='success'>✅ Yes</span>" : "<span class='error'>❌ No</span>") . "</p>";

        if (!$canTransitionToPOD) {
            echo "<div class='error'>";
            echo "<h3>❌ Status Transition Problem</h3>";
            echo "<p>The current status '{$currentStatus}' cannot transition to POD.</p>";
            echo "<p><strong>Allowed transitions from {$currentStatus}:</strong></p>";

            $allowedTransitions = [
                'Pending' => ['Processing', 'Completed', 'Delivery', 'Cancelled'],
                'Processing' => ['Completed', 'Delivery', 'Cancelled'],
                'Completed' => ['Delivery', 'POD', 'Cancelled', 'Returned'],
                'Delivery' => ['POD', 'Cancelled', 'Returned'],
                'POD' => ['Completed', 'Delivery', 'Cancelled', 'Returned'],
                'Cancelled' => [],
                'Returned' => [],
            ];

            $allowed = $allowedTransitions[$currentStatus] ?? [];
            if (empty($allowed)) {
                echo "<p class='error'>No transitions allowed from {$currentStatus}</p>";
            } else {
                echo "<ul>";
                foreach ($allowed as $status) {
                    echo "<li>{$status}</li>";
                }
                echo "</ul>";
            }

            echo "<p><strong>Solution:</strong> Change status to 'Completed' or 'Delivery' first, then to POD.</p>";
            echo "</div>";
        }

        echo "<h2>📦 Item Transactions Analysis</h2>";

        if ($sale->itemTransaction->count() == 0) {
            echo "<p class='error'>❌ No item transactions found for this sale!</p>";
            echo "<p>This sale has no items, so inventory deduction cannot occur.</p>";
        } else {
            echo "<table>";
            echo "<tr><th>Item ID</th><th>Item Name</th><th>Quantity</th><th>Unique Code</th><th>Tracking Type</th></tr>";

            foreach ($sale->itemTransaction as $transaction) {
                $itemName = $transaction->item->item_name ?? 'Unknown Item';
                echo "<tr>";
                echo "<td>{$transaction->item_id}</td>";
                echo "<td>{$itemName}</td>";
                echo "<td>{$transaction->quantity}</td>";
                echo "<td><strong>{$transaction->unique_code}</strong></td>";
                echo "<td>{$transaction->tracking_type}</td>";
                echo "</tr>";
            }
            echo "</table>";

            // Check if any transactions are already deducted
            $deductedCount = $sale->itemTransaction->where('unique_code', ItemTransactionUniqueCode::SALE->value)->count();
            $reservedCount = $sale->itemTransaction->where('unique_code', ItemTransactionUniqueCode::SALE_ORDER->value)->count();

            echo "<p><strong>Transaction Status:</strong></p>";
            echo "<ul>";
            echo "<li>Deducted (SALE): {$deductedCount}</li>";
            echo "<li>Reserved (SALE_ORDER): {$reservedCount}</li>";
            echo "</ul>";

            if ($sale->inventory_status === 'deducted' && $deductedCount > 0) {
                echo "<p class='success'>✅ Inventory is already deducted</p>";
            } elseif ($sale->inventory_status === 'deducted' && $deductedCount == 0) {
                echo "<p class='warning'>⚠️ Sale shows deducted but transactions are not updated</p>";
            }
        }

        echo "<h2>🧪 Test POD Status Update</h2>";

        if ($canTransitionToPOD && $sale->inventory_status !== 'deducted') {
            echo "<p class='info'>Testing POD status update...</p>";

            try {
                // Test the status update (without actually committing)
                $testResult = $salesStatusService->updateSalesStatus($sale, 'POD', [
                    'notes' => 'Debug test - POD status update'
                ]);

                if ($testResult['success']) {
                    echo "<p class='success'>✅ POD status update would succeed</p>";
                    echo "<p>Inventory updated: " . ($testResult['inventory_updated'] ? 'Yes' : 'No') . "</p>";

                    // Refresh sale to see changes
                    $sale->refresh();
                    echo "<p>New inventory status: <strong>{$sale->inventory_status}</strong></p>";
                    echo "<p>Inventory deducted at: {$sale->inventory_deducted_at}</p>";
                } else {
                    echo "<p class='error'>❌ POD status update failed: {$testResult['message']}</p>";
                }
            } catch (Exception $e) {
                echo "<p class='error'>❌ Exception during POD test: " . $e->getMessage() . "</p>";
                echo "<pre>" . $e->getTraceAsString() . "</pre>";
            }
        } else {
            echo "<p class='warning'>⚠️ Cannot test POD update:</p>";
            echo "<ul>";
            if (!$canTransitionToPOD) echo "<li>Status transition not allowed</li>";
            if ($sale->inventory_status === 'deducted') echo "<li>Inventory already deducted</li>";
            echo "</ul>";
        }

        echo "<h2>🔧 Troubleshooting Steps</h2>";
        echo "<ol>";

        if (!$canTransitionToPOD) {
            echo "<li class='error'><strong>Fix Status Transition:</strong> Change sale status to 'Completed' or 'Delivery' first</li>";
        }

        if ($sale->itemTransaction->count() == 0) {
            echo "<li class='error'><strong>Add Items:</strong> This sale has no items to deduct inventory from</li>";
        }

        echo "<li><strong>Check Logs:</strong> Look at Laravel logs for any errors during status update</li>";
        echo "<li><strong>Verify Service:</strong> Ensure SalesStatusService is properly instantiated</li>";
        echo "<li><strong>Check Database:</strong> Verify ItemTransactionService methods work correctly</li>";
        echo "</ol>";

        echo "<h2>📝 Manual Test Form</h2>";
        if ($canTransitionToPOD && $sale->inventory_status !== 'deducted') {
            echo "<form method='POST' action='?sale_id={$saleId}&action=test'>";
            echo "<input type='hidden' name='_token' value='" . csrf_token() . "'>";
            echo "<p><label>Notes: <textarea name='notes' required>Manual test of POD status update</textarea></label></p>";
            echo "<p><button type='submit' style='background: #007cba; color: white; padding: 10px; border: none; border-radius: 5px;'>Test POD Status Update</button></p>";
            echo "</form>";
        }

    } catch (Exception $e) {
        echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }

    // Handle manual test
    if ($_GET['action'] ?? null === 'test' && $_POST['notes'] ?? null) {
        echo "<hr><h2>🧪 Manual Test Results</h2>";
        try {
            $sale = Sale::findOrFail($saleId);
            $salesStatusService = app(SalesStatusService::class);

            $result = $salesStatusService->updateSalesStatus($sale, 'POD', [
                'notes' => $_POST['notes']
            ]);

            if ($result['success']) {
                echo "<p class='success'>✅ Manual test successful!</p>";
                echo "<p>Message: {$result['message']}</p>";
                echo "<p>Inventory updated: " . ($result['inventory_updated'] ? 'Yes' : 'No') . "</p>";
            } else {
                echo "<p class='error'>❌ Manual test failed: {$result['message']}</p>";
            }
        } catch (Exception $e) {
            echo "<p class='error'>❌ Manual test exception: " . $e->getMessage() . "</p>";
        }
    }
    ?>

    <hr>
    <p><a href="?">← Test Another Sale</a></p>
</body>
</html>
