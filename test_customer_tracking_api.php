<?php

/**
 * Customer Tracking API Test Script
 *
 * هذا الملف لاختبار API البحث عن الشحنات للعملاء
 */

// إعدادات الاختبار
$baseUrl = 'http://localhost:8000'; // غير هذا حسب الخادم الخاص بك
$apiUrl = $baseUrl . '/api/customer/tracking';

// بيانات اختبار
$testTrackingNumbers = [
    'RR123456789US',        // International format
    '1234567890123456',     // Numeric format
    'TN987654321',          // TN format
    '1Z12345E0205271688',   // UPS format
    'DHL123456789',         // Carrier prefix
    'INVALID123',           // Invalid format
];

/**
 * إرسال طلب HTTP
 */
function sendRequest($url, $data = null, $token = null) {
    $ch = curl_init();

    $headers = ['Content-Type: application/json'];
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 30,
    ]);

    if ($data) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'status_code' => $httpCode,
        'body' => json_decode($response, true),
        'raw' => $response
    ];
}

/**
 * طباعة النتائج بشكل منسق
 */
function printResult($title, $result) {
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "🧪 $title\n";
    echo str_repeat("=", 60) . "\n";
    echo "Status Code: " . $result['status_code'] . "\n";
    echo "Response:\n";
    echo json_encode($result['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
}

/**
 * اختبار التحقق من صحة رقم التتبع
 */
function testValidateTrackingNumber($apiUrl, $trackingNumbers) {
    echo "\n🔍 اختبار التحقق من صحة أرقام التتبع\n";
    echo str_repeat("-", 60) . "\n";

    foreach ($trackingNumbers as $trackingNumber) {
        $result = sendRequest($apiUrl . '/validate-public', [
            'tracking_number' => $trackingNumber
        ]);

        $status = $result['body']['valid'] ? '✅ صحيح' : '❌ غير صحيح';
        $pattern = $result['body']['pattern_matched'] ?? 'N/A';

        echo sprintf(
            "%-20s | %s | Pattern: %s\n",
            $trackingNumber,
            $status,
            $pattern
        );
    }
}

/**
 * اختبار البحث عن الشحنات
 */
function testSearchTracking($apiUrl, $trackingNumbers) {
    echo "\n🔎 اختبار البحث عن الشحنات\n";
    echo str_repeat("-", 60) . "\n";

    foreach ($trackingNumbers as $trackingNumber) {
        echo "\nاختبار رقم التتبع: $trackingNumber\n";

        $result = sendRequest($apiUrl . '/search-public', [
            'tracking_number' => $trackingNumber
        ]);

        if ($result['status_code'] === 200 && $result['body']['status']) {
            echo "✅ تم العثور على الشحنة\n";
            $data = $result['body']['data'];
            echo "   - رقم الطلب: " . ($data['order_info']['order_code'] ?? 'N/A') . "\n";
            echo "   - الحالة: " . ($data['tracking_info']['status'] ?? 'N/A') . "\n";
            echo "   - الناقل: " . ($data['carrier_info']['name'] ?? 'N/A') . "\n";
            echo "   - عدد الأحداث: " . ($data['statistics']['total_events'] ?? 0) . "\n";
        } elseif ($result['status_code'] === 404) {
            echo "❌ لم يتم العثور على الشحنة\n";
        } else {
            echo "⚠️  خطأ: " . ($result['body']['message'] ?? 'Unknown error') . "\n";
        }
    }
}

/**
 * اختبار الأخطاء والحالات الاستثنائية
 */
function testErrorCases($apiUrl) {
    echo "\n⚠️  اختبار الأخطاء والحالات الاستثنائية\n";
    echo str_repeat("-", 60) . "\n";

    // اختبار رقم تتبع فارغ
    $result = sendRequest($apiUrl . '/validate-public', [
        'tracking_number' => ''
    ]);
    printResult('رقم تتبع فارغ', $result);

    // اختبار رقم تتبع قصير جداً
    $result = sendRequest($apiUrl . '/validate-public', [
        'tracking_number' => '123'
    ]);
    printResult('رقم تتبع قصير', $result);

    // اختبار رقم تتبع طويل جداً
    $result = sendRequest($apiUrl . '/validate-public', [
        'tracking_number' => str_repeat('A', 100)
    ]);
    printResult('رقم تتبع طويل', $result);

    // اختبار بدون بيانات
    $result = sendRequest($apiUrl . '/search-public', []);
    printResult('بحث بدون بيانات', $result);
}

/**
 * اختبار الأداء
 */
function testPerformance($apiUrl, $iterations = 10) {
    echo "\n⚡ اختبار الأداء ($iterations تكرار)\n";
    echo str_repeat("-", 60) . "\n";

    $trackingNumber = 'RR123456789US';
    $times = [];

    for ($i = 0; $i < $iterations; $i++) {
        $start = microtime(true);

        sendRequest($apiUrl . '/validate-public', [
            'tracking_number' => $trackingNumber
        ]);

        $end = microtime(true);
        $times[] = ($end - $start) * 1000; // Convert to milliseconds
    }

    $avgTime = array_sum($times) / count($times);
    $minTime = min($times);
    $maxTime = max($times);

    echo "متوسط وقت الاستجابة: " . number_format($avgTime, 2) . " ms\n";
    echo "أسرع استجابة: " . number_format($minTime, 2) . " ms\n";
    echo "أبطأ استجابة: " . number_format($maxTime, 2) . " ms\n";
}

/**
 * اختبار شامل للـ API
 */
function runFullTest($apiUrl, $trackingNumbers) {
    echo "🚀 بدء اختبار Customer Tracking API\n";
    echo "Base URL: $apiUrl\n";
    echo "التاريخ: " . date('Y-m-d H:i:s') . "\n";

    // اختبار الاتصال
    echo "\n🔗 اختبار الاتصال بالخادم...\n";
    $pingResult = sendRequest(str_replace('/customer/tracking', '/ping', $apiUrl));
    if ($pingResult['status_code'] === 200) {
        echo "✅ الخادم يعمل بشكل طبيعي\n";
    } else {
        echo "❌ مشكلة في الاتصال بالخادم\n";
        return;
}

    // تشغيل الاختبارات
    testValidateTrackingNumber($apiUrl, $trackingNumbers);
    testSearchTracking($apiUrl, $trackingNumbers);
    testErrorCases($apiUrl);
    testPerformance($apiUrl, 5);

    echo "\n" . str_repeat("=", 60) . "\n";
    echo "✅ انتهى الاختبار بنجاح!\n";
    echo str_repeat("=", 60) . "\n";
}

// تشغيل الاختبار
try {
    runFullTest($apiUrl, $testTrackingNumbers);
} catch (Exception $e) {
    echo "❌ خطأ في الاختبار: " . $e->getMessage() . "\n";
}

?>
