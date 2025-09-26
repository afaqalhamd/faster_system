<?php
require_once 'vendor/autoload.php';

// Load Laravel application
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Sale\Sale;
use App\Services\SalesStatusService;

echo "Checking inventory behavior for post-delivery cancellations\n";
echo "========================================================\n\n";

// Find a sale with POD status and deducted inventory
$sale = Sale::where('sales_status', 'POD')
    ->where('inventory_status', 'deducted')
    ->first();

if (!$sale) {
    echo "No sale found with POD status and deducted inventory\n";
    exit(1);
}

echo "Found sale ID: " . $sale->id . "\n";
echo "Current sales_status: " . $sale->sales_status . "\n";
echo "Current inventory_status: " . $sale->inventory_status . "\n";
echo "post_delivery_action: " . ($sale->post_delivery_action ?? 'NULL') . "\n";
echo "post_delivery_action_at: " . ($sale->post_delivery_action_at ?? 'NULL') . "\n\n";

// Simulate changing from POD to Cancelled
echo "Simulating change from POD to Cancelled...\n";
$salesStatusService = app(SalesStatusService::class);

$result = $salesStatusService->updateSalesStatus($sale, 'Cancelled', [
    'notes' => 'Testing post-delivery cancellation'
]);

echo "Update result:\n";
print_r($result);

// Refresh the sale to see the new status
$sale->refresh();

echo "\nAfter update:\n";
echo "New sales_status: " . $sale->sales_status . "\n";
echo "New inventory_status: " . $sale->inventory_status . "\n";
echo "post_delivery_action: " . ($sale->post_delivery_action ?? 'NULL') . "\n";
echo "post_delivery_action_at: " . ($sale->post_delivery_action_at ?? 'NULL') . "\n";

if ($sale->inventory_status === 'deducted_delivered') {
    echo "\n✓ CORRECT: Inventory status is 'deducted_delivered' - inventory should remain deducted\n";
} else if ($sale->inventory_status === 'restored') {
    echo "\n✗ INCORRECT: Inventory status is 'restored' - inventory was incorrectly returned\n";
} else {
    echo "\n? UNEXPECTED: Inventory status is '" . $sale->inventory_status . "'\n";
}
