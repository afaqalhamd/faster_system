<?php

require_once 'vendor/autoload.php';

use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Events\EventServiceProvider;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Routing\Router;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\Facade;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Engines\PhpEngine;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;

// Create the application container
$app = new Container();
Facade::setFacadeApplication($app);

// Bind the container to itself for easy access
$app->instance('app', $app);

// Create the event dispatcher
$app->singleton('events', function ($app) {
    return (new EventServiceProvider($app))->register();
});

// Create the request
$app->singleton('request', function ($app) {
    return Request::capture();
});

// Create the view factory
$app->singleton('view', function ($app) {
    $resolver = new EngineResolver();
    $resolver->register('php', function () {
        return new PhpEngine();
    });

    $finder = new FileViewFinder(new Filesystem(), [__DIR__.'/resources/views']);
    $factory = new Factory($resolver, $finder, new Dispatcher($app));

    return $factory;
});

// Create the URL generator
$app->singleton('url', function ($app) {
    return new UrlGenerator($app['router'], $app['request']);
});

// Create the redirector
$app->singleton('redirect', function ($app) {
    return new Redirector($app['url']);
});

// Load the Laravel application
$app = require_once 'bootstrap/app.php';

// Bootstrap the application
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Test the sale order status functionality
try {
    // Get the first sale order
    $saleOrder = App\Models\Sale\SaleOrder::first();

    if ($saleOrder) {
        echo "Found sale order with ID: " . $saleOrder->id . "\n";
        echo "Current status: " . $saleOrder->order_status . "\n";

        // Test the status update service
        $statusService = new App\Services\SaleOrderStatusService(
            new App\Services\ItemTransactionService()
        );

        // Try to update the status to Processing
        $result = $statusService->updateSaleOrderStatus($saleOrder, 'Processing', [
            'notes' => 'Testing status update'
        ]);

        echo "Status update result: " . ($result['success'] ? 'SUCCESS' : 'FAILED') . "\n";
        if (!$result['success']) {
            echo "Error: " . $result['message'] . "\n";
        }

        // Refresh the sale order and check the new status
        $saleOrder->refresh();
        echo "New status: " . $saleOrder->order_status . "\n";

        // Check the status history
        $history = $statusService->getStatusHistory($saleOrder);
        echo "Status history entries: " . count($history) . "\n";

        foreach ($history as $entry) {
            echo "- " . $entry['previous_status'] . " -> " . $entry['new_status'] . " at " . $entry['changed_at'] . "\n";
        }
    } else {
        echo "No sale orders found in the database.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
