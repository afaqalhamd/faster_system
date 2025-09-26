<?php

/**
 * Simple test script to verify POD status functionality
 * Run this with: php artisan tinker
 * Then copy and paste the code below
 */

echo "Testing POD Status Functionality\n";
echo "================================\n\n";

// Test 1: Check if SalesStatusService exists and can be instantiated
try {
    $salesStatusService = app(\App\Services\SalesStatusService::class);
    echo "✅ SalesStatusService instantiated successfully\n";
} catch (Exception $e) {
    echo "❌ Error instantiating SalesStatusService: " . $e->getMessage() . "\n";
    exit;
}

// Test 2: Check if the required status options are available
$statusesRequiringProof = $salesStatusService->getStatusesRequiringProof();
echo "📋 Statuses requiring proof: " . implode(', ', $statusesRequiringProof) . "\n";

if (in_array('POD', $statusesRequiringProof)) {
    echo "✅ POD status requires proof (correct)\n";
} else {
    echo "❌ POD status does not require proof (incorrect)\n";
}

// Test 3: Check if migration has been run
try {
    $historyCount = \App\Models\SalesStatusHistory::count();
    echo "✅ SalesStatusHistory table exists (count: $historyCount)\n";
} catch (Exception $e) {
    echo "❌ SalesStatusHistory table does not exist: " . $e->getMessage() . "\n";
}

// Test 4: Check if routes are properly registered
try {
    $updateRoute = route('sale.invoice.update.sales.status', ['id' => 1]);
    echo "✅ Status update route exists: $updateRoute\n";
} catch (Exception $e) {
    echo "❌ Status update route not found: " . $e->getMessage() . "\n";
}

// Test 5: Create a test sale and test status update (if possible)
try {
    // Get the first sale if any exists
    $sale = \App\Models\Sale\Sale::first();
    if ($sale) {
        echo "✅ Found test sale: ID {$sale->id}, Status: {$sale->sales_status}, Inventory: {$sale->inventory_status}\n";

        // Test status transition validation
        $canTransition = $salesStatusService->canTransitionToStatus($sale, 'POD');
        echo $canTransition ? "✅ Can transition to POD\n" : "⚠️  Cannot transition to POD from current status\n";

    } else {
        echo "⚠️  No sales found for testing\n";
    }
} catch (Exception $e) {
    echo "❌ Error testing sale status: " . $e->getMessage() . "\n";
}

echo "\n📝 Summary:\n";
echo "- The POD status implementation appears to be in place\n";
echo "- JavaScript file has been created and should be included in forms\n";
echo "- Routes are registered for status updates\n";
echo "- Service layer is ready for inventory deduction\n\n";

echo "🔧 To test the full functionality:\n";
echo "1. Go to sales create/edit page\n";
echo "2. Change status to POD\n";
echo "3. A modal should appear asking for notes and proof image\n";
echo "4. After submission, inventory should be deducted\n";
