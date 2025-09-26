<?php
// Test script to check delivery login route and user

require_once 'vendor/autoload.php';

use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Routing\RouteCollection;

// This is just to check if the route exists
echo "Testing delivery login route...\n";

// Check if user exists and has correct role
require_once 'bootstrap/app.php';

// Since we can't easily test the route this way, let's just output the correct way to test
echo "To test the delivery login, use the following format:\n";
echo "POST request to: http://192.168.0.238/api/delivery/login\n";
echo "With JSON body:\n";
echo "{\n";
echo "  \"email\": \"dhl@gmail.com\",\n";
echo "  \"password\": \"12345678\"\n";
echo "}\n";
echo "\n";
echo "Do NOT use query parameters like ?email=...&password=...\n";
echo "Use a tool like Postman or curl to send a POST request with JSON data.\n";

// Check user role
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where('email', 'dhl@gmail.com')->first();
if ($user) {
    echo "\nUser found:\n";
    echo "- Email: " . $user->email . "\n";
    echo "- Role: " . ($user->role ? $user->role->name : 'No role') . "\n";
    echo "- Carrier ID: " . $user->carrier_id . "\n";
} else {
    echo "\nUser not found!\n";
}
