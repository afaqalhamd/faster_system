<?php

require_once 'vendor/autoload.php';

// Load Laravel application
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Sale\SaleOrder;
use App\Models\Sale\ShipmentTracking;

// Test: Get a sale order with tracking data
$saleOrder = SaleOrder::with('shipmentTrackings.carrier')->first();

if ($saleOrder) {
    echo "Sale Order ID: " . $saleOrder->id . "\n";
    echo "Order Code: " . $saleOrder->order_code . "\n";

    if ($saleOrder->shipmentTrackings->count() > 0) {
        echo "Tracking Records:\n";
        foreach ($saleOrder->shipmentTrackings as $tracking) {
            echo "  - Tracking ID: " . $tracking->id . "\n";
            echo "  - Carrier: " . ($tracking->carrier ? $tracking->carrier->name : 'N/A') . "\n";
            echo "  - Tracking Number: " . ($tracking->tracking_number ?? 'N/A') . "\n";
            echo "  - Status: " . $tracking->status . "\n";
        }
    } else {
        echo "No tracking records found for this sale order.\n";
    }
} else {
    echo "No sale orders found in the database.\n";
}
