<?php

require_once 'vendor/autoload.php';

// Load Laravel application
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Sale\ShipmentTracking;

// Create a new tracking record
$tracking = new ShipmentTracking();
$tracking->sale_order_id = 1;
$tracking->tracking_number = 'TRK123456';
$tracking->status = 'In Transit';
$tracking->save();

echo "Tracking record created with ID: " . $tracking->id . "\n";
