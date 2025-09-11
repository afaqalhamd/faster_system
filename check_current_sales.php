<?php
require_once 'vendor/autoload.php';

// Load Laravel application
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Sale\Sale;

echo "Current Sales Status Overview\n";
echo "============================\n\n";

// Get a few sales to examine their status
$sales = Sale::orderBy('id', 'desc')->limit(10)->get();

foreach ($sales as $sale) {
    echo "Sale ID: " . $sale->id . "\n";
    echo "  Sales Status: " . $sale->sales_status . "\n";
    echo "  Inventory Status: " . $sale->inventory_status . "\n";
    echo "  Post Delivery Action: " . ($sale->post_delivery_action ?? 'NULL') . "\n";
    echo "  Post Delivery Action At: " . ($sale->post_delivery_action_at ?? 'NULL') . "\n";
    echo "  Inventory Deducted At: " . ($sale->inventory_deducted_at ?? 'NULL') . "\n";
    echo "  ---\n";
}

echo "\nStatus Distribution:\n";
$statusCounts = Sale::select('sales_status', 'inventory_status')
    ->selectRaw('count(*) as count')
    ->groupBy('sales_status', 'inventory_status')
    ->orderBy('sales_status')
    ->get();

foreach ($statusCounts as $status) {
    echo "  " . $status->sales_status . " / " . $status->inventory_status . ": " . $status->count . " sales\n";
}
