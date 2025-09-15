<?php
// Test file to verify translation functionality
require_once 'vendor/autoload.php';

// Simulate Laravel environment
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\App;

// Test translations
echo "English translations:\n";
echo "Pending: " . __('purchase.pending') . "\n";
echo "Processing: " . __('purchase.processing') . "\n";
echo "Completed: " . __('purchase.completed') . "\n";
echo "Shipped: " . __('purchase.shipped') . "\n";
echo "ROG: " . __('purchase.rog') . "\n";
echo "Cancelled: " . __('purchase.cancelled') . "\n";
echo "Returned: " . __('purchase.returned') . "\n";

// Switch to Arabic
App::setLocale('ar');

echo "\nArabic translations:\n";
echo "Pending: " . __('purchase.pending') . "\n";
echo "Processing: " . __('purchase.processing') . "\n";
echo "Completed: " . __('purchase.completed') . "\n";
echo "Shipped: " . __('purchase.shipped') . "\n";
echo "ROG: " . __('purchase.rog') . "\n";
echo "Cancelled: " . __('purchase.cancelled') . "\n";
echo "Returned: " . __('purchase.returned') . "\n";
