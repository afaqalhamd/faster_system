<?php
// Simple test to check if the sales datatable endpoint works

require_once 'vendor/autoload.php';

use Illuminate\Http\Request;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Create a mock request
$request = Request::create('/sale/invoice/datatable-list', 'GET');

// Try to handle the request through the router
try {
    $response = $app['router']->dispatch($request);

    echo "Response Status: " . $response->getStatusCode() . "\n";
    echo "Response Content:\n";

    // Get the content
    $content = $response->getContent();
    echo $content . "\n";

} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
