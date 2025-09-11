<?php
/**
 * Debug Inventory Issue - Complete Investigation
 * Access: http://your-domain/debug-inventory-issue.php
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
use App\Models\Items\ItemTransaction;
use App\Enums\ItemTransactionUniqueCode;

?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Inventory Issue</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .info { color: blue; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .section { margin: 20px 0; border: 1px solid #ddd; padding: 15px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>🔍 Complete Inventory System Debug</h1>

    <?php
    try {
        echo '<div class="section">';
        echo '<h2>1. 🧪 Service Instantiation Test</h2>';

        // Test service instantiation
        try {
            $salesStatusService = app(SalesStatusService::class);
            echo "<p class='success'>✅ SalesStatusService instantiated successfully: " . get_class($salesStatusService) . "</p>";
        } catch (Exception $e) {
            echo "<p class='error'>❌ Failed to instantiate SalesStatusService: " . $e->getMessage() . "</p>";
        }

        try {
            $itemTransactionService = app(ItemTransactionService::class);
            echo "<p class='success'>✅ ItemTransactionService instantiated successfully: " . get_class($itemTransactionService) . "</p>";
        } catch (Exception $e) {
            echo "<p class='error'>❌ Failed to instantiate ItemTransactionService: " . $e->getMessage() . "</p>";
        }
        echo '</div>';

        echo '<div class="section">';
        echo '<h2>2. 📊 Recent Sales Analysis</h2>';

        $recentSales = Sale::with(['itemTransaction', 'party'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        if ($recentSales->count() == 0) {
            echo "<p class='warning'>⚠️ No sales found in the system</p>";
        } else {
            echo "<table>";
            echo "<tr><th>ID</th><th>Customer</th><th>Sales Status</th><th>Inventory Status</th><th>Items Count</th><th>Created</th></tr>";

            foreach ($recentSales as $sale) {
                $customerName = $sale->party ? $sale->party->getFullName() : 'Unknown';
                echo "<tr>";
                echo "<td><a href='?sale_id={$sale->id}'>{$sale->id}</a></td>";
                echo "<td>{$customerName}</td>";
                echo "<td><strong>{$sale->sales_status}</strong></td>";
                echo "<td><strong>{$sale->inventory_status}</strong></td>";
                echo "<td>{$sale->itemTransaction->count()}</td>";
                echo "<td>{$sale->created_at->format('Y-m-d H:i')}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        echo '</div>';

        echo '<div class="section">';
        echo '<h2>3. 🎯 Specific Sale Analysis</h2>';

        $saleId = $_GET['sale_id'] ?? $recentSales->first()->id ?? 1;
        echo "<p><strong>Analyzing Sale ID:</strong> {$saleId}</p>";

        try {
            $sale = Sale::with(['itemTransaction', 'party'])->findOrFail($saleId);

            echo "<table>";
            echo "<tr><th>Property</th><th>Value</th></tr>";
            echo "<tr><td>Sale ID</td><td>{$sale->id}</td></tr>";
            echo "<tr><td>Sale Code</td><td>{$sale->sale_code}</td></tr>";
            echo "<tr><td>Customer</td><td>{$sale->party->getFullName()}</td></tr>";
            echo "<tr><td>Sales Status</td><td><strong>{$sale->sales_status}</strong></td></tr>";
            echo "<tr><td>Inventory Status</td><td><strong>{$sale->inventory_status}</strong></td></tr>";
            echo "<tr><td>Inventory Deducted At</td><td>{$sale->inventory_deducted_at}</td></tr>";
            echo "<tr><td>Total Items</td><td>{$sale->itemTransaction->count()}</td></tr>";
            echo "<tr><td>Grand Total</td><td>{$sale->grand_total}</td></tr>";
            echo "</table>";

        } catch (Exception $e) {
            echo "<p class='error'>❌ Failed to load sale: " . $e->getMessage() . "</p>";
        }
        echo '</div>';

        echo '<div class="section">';
        echo '<h2>4. 📦 Item Transactions Analysis</h2>';

        if (isset($sale) && $sale->itemTransaction->count() > 0) {
            echo "<table>";
            echo "<tr><th>Transaction ID</th><th>Item ID</th><th>Item Name</th><th>Quantity</th><th>Unique Code</th><th>Tracking Type</th></tr>";

            foreach ($sale->itemTransaction as $transaction) {
                $itemName = $transaction->item->item_name ?? 'Unknown Item';
                echo "<tr>";
                echo "<td>{$transaction->id}</td>";
                echo "<td>{$transaction->item_id}</td>";
                echo "<td>{$itemName}</td>";
                echo "<td>{$transaction->quantity}</td>";
                echo "<td><strong>{$transaction->unique_code}</strong></td>";
                echo "<td>{$transaction->tracking_type}</td>";
                echo "</tr>";
            }
            echo "</table>";

            // Analyze transaction codes
            $saleOrderCount = $sale->itemTransaction->where('unique_code', ItemTransactionUniqueCode::SALE_ORDER->value)->count();
            $saleCount = $sale->itemTransaction->where('unique_code', ItemTransactionUniqueCode::SALE->value)->count();

            echo "<p><strong>Transaction Code Summary:</strong></p>";
            echo "<ul>";
            echo "<li>SALE_ORDER (Reserved): {$saleOrderCount}</li>";
            echo "<li>SALE (Deducted): {$saleCount}</li>";
            echo "</ul>";

        } else {
            echo "<p class='warning'>⚠️ No item transactions found for this sale</p>";
        }
        echo '</div>';

        echo '<div class="section">';
        echo '<h2>5. 🔧 Test Status Transition</h2>';

        if (isset($sale)) {
            // Test transition to POD
            $canTransitionToPOD = false;
            try {
                $canTransitionToPOD = $salesStatusService->canTransitionToStatus($sale, 'POD');
                echo "<p><strong>Can transition to POD:</strong> " . ($canTransitionToPOD ? "<span class='success'>✅ Yes</span>" : "<span class='error'>❌ No</span>") . "</p>";
            } catch (Exception $e) {
                echo "<p class='error'>❌ Error checking transition: " . $e->getMessage() . "</p>";
            }

            // Show allowed transitions
            $currentStatus = $sale->sales_status;
            $allowedTransitions = [
                'Pending' => ['Processing', 'Completed', 'Delivery', 'POD', 'Cancelled'],
                'Processing' => ['Completed', 'Delivery', 'POD', 'Cancelled'],
                'Completed' => ['Delivery', 'POD', 'Cancelled', 'Returned'],
                'Delivery' => ['POD', 'Cancelled', 'Returned'],
                'POD' => ['Completed', 'Delivery', 'Cancelled', 'Returned'],
                'Cancelled' => [],
                'Returned' => [],
            ];

            $allowed = $allowedTransitions[$currentStatus] ?? [];
            echo "<p><strong>Allowed transitions from {$currentStatus}:</strong></p>";
            if (empty($allowed)) {
                echo "<p class='warning'>No transitions allowed</p>";
            } else {
                echo "<ul>";
                foreach ($allowed as $status) {
                    echo "<li>{$status}</li>";
                }
                echo "</ul>";
            }
        }
        echo '</div>';

        echo '<div class="section">';
        echo '<h2>6. 🧪 Test Inventory Deduction Process</h2>';

        if (isset($sale) && $canTransitionToPOD) {
            echo '<form method="POST" style="margin: 10px 0;">';
            echo '<input type="hidden" name="test_inventory" value="1">';
            echo '<input type="hidden" name="sale_id" value="' . $sale->id . '">';
            echo '<p><label>Notes: <textarea name="notes" required>Test inventory deduction - ' . now() . '</textarea></label></p>';
            echo '<p><button type="submit" style="background: #007cba; color: white; padding: 10px; border: none; border-radius: 5px;">🧪 Test Inventory Deduction</button></p>';
            echo '</form>';
        } else {
            echo "<p class='info'>ℹ️ Cannot test inventory deduction:</p>";
            echo "<ul>";
            if (!isset($sale)) echo "<li>Sale not found</li>";
            if (isset($sale) && !$canTransitionToPOD) echo "<li>Cannot transition to POD from current status</li>";
            if (isset($sale) && $sale->inventory_status === 'deducted') echo "<li>Inventory already deducted</li>";
            echo "</ul>";
        }
        echo '</div>';

        echo '<div class="section">';
        echo '<h2>7. 📋 ItemTransactionService Method Test</h2>';

        if (isset($sale) && $sale->itemTransaction->count() > 0) {
            $firstTransaction = $sale->itemTransaction->first();
            echo "<p><strong>Testing updateItemGeneralQuantityWarehouseWise for item ID:</strong> {$firstTransaction->item_id}</p>";

            try {
                $result = $itemTransactionService->updateItemGeneralQuantityWarehouseWise($firstTransaction->item_id);
                echo "<p class='success'>✅ updateItemGeneralQuantityWarehouseWise executed successfully: " . ($result ? 'true' : 'false') . "</p>";
            } catch (Exception $e) {
                echo "<p class='error'>❌ updateItemGeneralQuantityWarehouseWise failed: " . $e->getMessage() . "</p>";
                echo "<pre>" . $e->getTraceAsString() . "</pre>";
            }
        }
        echo '</div>';

    } catch (Exception $e) {
        echo "<p class='error'>❌ Critical Error: " . $e->getMessage() . "</p>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }

    // Handle test form submission
    if ($_POST['test_inventory'] ?? false) {
        echo '<div class="section">';
        echo '<h2>🧪 Test Results</h2>';

        try {
            $sale = Sale::findOrFail($_POST['sale_id']);

            echo "<p class='info'>Starting inventory deduction test...</p>";

            $result = $salesStatusService->updateSalesStatus($sale, 'POD', [
                'notes' => $_POST['notes']
            ]);

            if ($result['success']) {
                echo "<p class='success'>✅ Test successful!</p>";
                echo "<p><strong>Message:</strong> {$result['message']}</p>";
                echo "<p><strong>Inventory Updated:</strong> " . ($result['inventory_updated'] ? 'Yes' : 'No') . "</p>";

                // Refresh and show new status
                $sale->refresh();
                echo "<p><strong>New Sales Status:</strong> {$sale->sales_status}</p>";
                echo "<p><strong>New Inventory Status:</strong> {$sale->inventory_status}</p>";
                echo "<p><strong>Inventory Deducted At:</strong> {$sale->inventory_deducted_at}</p>";
            } else {
                echo "<p class='error'>❌ Test failed: {$result['message']}</p>";
            }

        } catch (Exception $e) {
            echo "<p class='error'>❌ Test exception: " . $e->getMessage() . "</p>";
            echo "<pre>" . $e->getTraceAsString() . "</pre>";
        }

        echo '</div>';
    }
    ?>

    <hr>
    <h2>🔗 Quick Actions</h2>
    <ul>
        <li><a href="?">🔄 Refresh Analysis</a></li>
        <li><a href="/sale/invoice">📋 Go to Sales List</a></li>
        <li><a href="/sale/invoice/create">➕ Create New Sale</a></li>
    </ul>
</body>
</html>
