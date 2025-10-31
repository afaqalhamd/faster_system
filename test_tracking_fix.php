<?php

/**
 * Quick test for the fixed tracking API
 */

$baseUrl = 'http://192.168.0.145';
$trackingNumber = 'FAT251028406724';

echo "🧪 Testing Fixed Tracking API\n";
echo "============================\n";
echo "Base URL: $baseUrl\n";
echo "Tracking Number: $trackingNumber\n\n";

/**
 * Test function
 */
function testApi($url, $method = 'GET', $data = null) {
    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    ]);

    if ($method === 'POST' && $data) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    return [
        'status_code' => $httpCode,
        'body' => json_decode($response, true),
        'raw' => $response,
        'error' => $error
    ];
}

// Test 1: GET with query parameter (your original request)
echo "1️⃣ Testing GET with query parameter:\n";
echo "URL: $baseUrl/api/customer/tracking/search-public?tracking_number=$trackingNumber\n";
$result1 = testApi("$baseUrl/api/customer/tracking/search-public?tracking_number=$trackingNumber");
echo "Status: " . $result1['status_code'] . "\n";
if ($result1['body']) {
    echo "Response: " . json_encode($result1['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
} else {
    echo "Raw Response: " . $result1['raw'] . "\n";
    echo "Error: " . $result1['error'] . "\n";
}

echo "\n" . str_repeat("-", 50) . "\n\n";

// Test 2: POST with JSON body
echo "2️⃣ Testing POST with JSON body:\n";
echo "URL: $baseUrl/api/customer/tracking/search-public\n";
$result2 = testApi("$baseUrl/api/customer/tracking/search-public", 'POST', [
    'tracking_number' => $trackingNumber
]);
echo "Status: " . $result2['status_code'] . "\n";
if ($result2['body']) {
    echo "Response: " . json_encode($result2['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
} else {
    echo "Raw Response: " . $result2['raw'] . "\n";
    echo "Error: " . $result2['error'] . "\n";
}

echo "\n" . str_repeat("-", 50) . "\n\n";

// Test 3: Validate tracking number
echo "3️⃣ Testing tracking number validation:\n";
echo "URL: $baseUrl/api/customer/tracking/validate-public?tracking_number=$trackingNumber\n";
$result3 = testApi("$baseUrl/api/customer/tracking/validate-public?tracking_number=$trackingNumber");
echo "Status: " . $result3['status_code'] . "\n";
if ($result3['body']) {
    echo "Response: " . json_encode($result3['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
} else {
    echo "Raw Response: " . $result3['raw'] . "\n";
    echo "Error: " . $result3['error'] . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "✅ Test completed!\n";

?>
