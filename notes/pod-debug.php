<?php
// Simple POD Status Debug Test
// Run this by visiting: http://your-domain/pod-debug.php

// Check if Laravel is available
if (!function_exists('csrf_token')) {
    die('This needs to be run in a Laravel environment');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>POD Status Debug</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
    </style>
</head>
<body>
    <h1>POD Status Functionality Debug</h1>

    <div class="test-section">
        <h2>1. CSRF Token Test</h2>
        <button id="testCsrf">Test CSRF Token</button>
        <div id="csrfResults"></div>
    </div>

    <div class="test-section">
        <h2>2. Route Test</h2>
        <button id="testRoute">Test Status Update Route</button>
        <div id="routeResults"></div>
    </div>

    <div class="test-section">
        <h2>3. JavaScript Module Test</h2>
        <button id="testJs">Test JavaScript Module</button>
        <div id="jsResults"></div>
    </div>

    <script>
        $(document).ready(function() {
            console.log('Debug page loaded');

            // Test 1: CSRF Token
            $('#testCsrf').click(function() {
                const csrfToken = $('meta[name="csrf-token"]').attr('content');
                let html = '<h3>CSRF Token Test Results:</h3>';

                if (csrfToken) {
                    html += `<p class="success">✅ CSRF Token found: ${csrfToken.substring(0, 10)}...</p>`;
                    html += `<p class="info">Token length: ${csrfToken.length} characters</p>`;
                } else {
                    html += '<p class="error">❌ CSRF Token NOT found!</p>';
                }

                $('#csrfResults').html(html);
                console.log('CSRF Token:', csrfToken);
            });

            // Test 2: Route Test
            $('#testRoute').click(function() {
                const csrfToken = $('meta[name="csrf-token"]').attr('content');
                const testSaleId = 1; // Use an existing sale ID

                if (!csrfToken) {
                    $('#routeResults').html('<p class="error">❌ No CSRF token available for route test</p>');
                    return;
                }

                $.ajax({
                    url: `/sale/invoice/update-sales-status/${testSaleId}`,
                    method: 'POST',
                    data: {
                        'sales_status': 'Processing',
                        'notes': 'Debug test notes',
                        '_token': csrfToken
                    },
                    success: function(response) {
                        $('#routeResults').html('<p class="success">✅ Route test successful!</p><pre>' + JSON.stringify(response, null, 2) + '</pre>');
                    },
                    error: function(xhr) {
                        let errorHtml = '<p class="error">❌ Route test failed:</p>';
                        errorHtml += '<p>Status: ' + xhr.status + '</p>';
                        errorHtml += '<p>Response: ' + xhr.responseText + '</p>';
                        $('#routeResults').html(errorHtml);
                        console.log('Route test error:', xhr);
                    }
                });
            });

            // Test 3: JavaScript Module
            $('#testJs').click(function() {
                let html = '<h3>JavaScript Module Test:</h3>';

                if (typeof window.salesStatusManager !== 'undefined') {
                    html += '<p class="success">✅ SalesStatusManager is loaded</p>';
                } else {
                    html += '<p class="error">❌ SalesStatusManager is NOT loaded</p>';
                }

                // Check if jQuery is working
                if (typeof $ !== 'undefined') {
                    html += '<p class="success">✅ jQuery is loaded</p>';
                } else {
                    html += '<p class="error">❌ jQuery is NOT loaded</p>';
                }

                $('#jsResults').html(html);
            });
        });
    </script>
</body>
</html>
