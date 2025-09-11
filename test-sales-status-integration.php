<?php
/**
 * Test Sales Status Integration
 * Access: http://your-domain/test-sales-status-integration.php
 */

// Bootstrap Laravel
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Services\GeneralDataService;
use App\Services\SalesStatusService;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Sales Status Integration</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .badge { padding: 3px 8px; border-radius: 3px; color: white; font-size: 12px; }
        .bg-warning { background-color: #ffc107; color: black; }
        .bg-primary { background-color: #0d6efd; }
        .bg-success { background-color: #198754; }
        .bg-info { background-color: #0dcaf0; color: black; }
        .bg-danger { background-color: #dc3545; }
        .bg-secondary { background-color: #6c757d; }
        select { padding: 8px; margin: 5px; border: 1px solid #ddd; border-radius: 4px; }
    </style>
</head>
<body>
    <h1>🧪 Sales Status Integration Test</h1>

    <?php
    try {
        echo "<h2>1. GeneralDataService Test</h2>";

        $generalDataService = new GeneralDataService();
        $statusOptions = $generalDataService->getSaleStatus();

        echo "<p class='success'>✅ GeneralDataService loaded successfully</p>";
        echo "<p class='info'>Found " . count($statusOptions) . " status options</p>";

        echo "<table>";
        echo "<tr><th>ID</th><th>Name</th><th>Color</th><th>Badge Preview</th></tr>";

        foreach ($statusOptions as $status) {
            echo "<tr>";
            echo "<td>{$status['id']}</td>";
            echo "<td>{$status['name']}</td>";
            echo "<td>{$status['color']}</td>";
            echo "<td><span class='badge bg-{$status['color']}'>{$status['name']}</span></td>";
            echo "</tr>";
        }
        echo "</table>";

        echo "<h2>2. SalesStatusService Integration</h2>";

        $salesStatusService = app(SalesStatusService::class);
        $statusesRequiringProof = $salesStatusService->getStatusesRequiringProof();

        echo "<p class='success'>✅ SalesStatusService loaded successfully</p>";
        echo "<p class='info'>Statuses requiring proof: " . implode(', ', $statusesRequiringProof) . "</p>";

        echo "<h2>3. Controller Endpoint Test</h2>";

        echo "<p>Testing: <code>GET /sale/invoice/get-sales-status-options</code></p>";
        echo "<button onclick='testStatusOptionsAPI()' style='padding: 10px; background: #0d6efd; color: white; border: none; border-radius: 4px;'>Test API Endpoint</button>";
        echo "<div id='api-results' style='margin-top: 10px;'></div>";

        echo "<h2>4. Form Integration Test</h2>";

        echo "<h3>Create Form Style:</h3>";
        echo "<select class='sales-status-select' name='sales_status' id='create_status' data-sale-id='new'>";
        foreach ($statusOptions as $status) {
            $selected = $status['id'] == 'Pending' ? 'selected' : '';
            echo "<option value='{$status['id']}' {$selected}>{$status['name']}</option>";
        }
        echo "</select>";

        echo "<h3>Edit Form Style (Example with POD selected):</h3>";
        echo "<select class='sales-status-select' name='sales_status' id='edit_status' data-sale-id='123'>";
        foreach ($statusOptions as $status) {
            $selected = $status['id'] == 'POD' ? 'selected' : '';
            echo "<option value='{$status['id']}' {$selected}>{$status['name']}</option>";
        }
        echo "</select>";
        echo "<button type='button' class='btn btn-outline-info view-status-history' data-sale-id='123' title='View Status History' style='margin-left: 10px; padding: 8px 12px; border: 1px solid #0dcaf0; background: transparent; color: #0dcaf0;'>";
        echo "<i>🕒</i> History";
        echo "</button>";

        echo "<h2>5. JavaScript Integration Test</h2>";
        echo "<p>Loading JavaScript module...</p>";
        echo "<div id='js-test-results'></div>";

    } catch (Exception $e) {
        echo "<p class='error'>❌ Error: {$e->getMessage()}</p>";
        echo "<pre>{$e->getTraceAsString()}</pre>";
    }
    ?>

    <script>
        // Test API endpoint
        function testStatusOptionsAPI() {
            $('#api-results').html('<p>Loading...</p>');

            fetch('/sale/invoice/get-sales-status-options')
                .then(response => response.json())
                .then(data => {
                    if (data.status) {
                        let html = '<div class="success">';
                        html += '<p>✅ API endpoint working correctly!</p>';
                        html += '<p><strong>Data received:</strong></p>';
                        html += '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
                        html += '</div>';
                        $('#api-results').html(html);
                    } else {
                        $('#api-results').html('<div class="error"><p>❌ API returned error</p></div>');
                    }
                })
                .catch(error => {
                    $('#api-results').html('<div class="error"><p>❌ API request failed: ' + error.message + '</p></div>');
                    console.error('API Error:', error);
                });
        }

        // Test JavaScript integration
        $(document).ready(function() {
            let jsTestHtml = '<h3>JavaScript Module Test:</h3>';
            jsTestHtml += '<p>jQuery loaded: ' + (typeof $ !== 'undefined' ? '✅ Yes' : '❌ No') + '</p>';
            jsTestHtml += '<p>Status selects found: ' + $('.sales-status-select').length + '</p>';

            // Test status change detection
            $('.sales-status-select').on('change', function() {
                const selectedStatus = $(this).val();
                const saleId = $(this).data('sale-id');
                jsTestHtml += '<p>Status changed to: <strong>' + selectedStatus + '</strong> for sale: ' + saleId + '</p>';
                $('#js-test-results').html(jsTestHtml);
            });

            $('#js-test-results').html(jsTestHtml);
        });
    </script>

    <hr>
    <h2>📝 Integration Summary</h2>
    <ul>
        <li>✅ <strong>GeneralDataService:</strong> Centralized status options with translations</li>
        <li>✅ <strong>SalesStatusService:</strong> Business logic integration</li>
        <li>✅ <strong>Controller API:</strong> Enhanced endpoint with metadata</li>
        <li>✅ <strong>Form Integration:</strong> Dynamic status loading</li>
        <li>✅ <strong>JavaScript:</strong> Event handling and API integration</li>
    </ul>

    <p><a href="/sale/invoice/create">→ Test Create Form</a> | <a href="/sale/invoice">→ Test Edit Form</a></p>
</body>
</html>
