<?php

/**
 * cURL Integration Example
 *
 * This example shows how to use Aluvia proxies with cURL for web scraping
 */

require_once '../src/index.php';

use Aluvia\Aluvia;
use Aluvia\Exceptions\AluviaException;

// Replace with your actual API token
$apiToken = 'your-api-token-here';

/**
 * Make an HTTP request through Aluvia proxy
 */
function makeProxyRequest($proxy, $url, $options = [])
{
    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',

        // Proxy settings
        CURLOPT_PROXY => $proxy->getHost() . ':' . $proxy->getHttpPort(),
        CURLOPT_PROXYUSERPWD => $proxy->getUsername() . ':' . $proxy->getPassword(),
        CURLOPT_PROXYTYPE => CURLPROXY_HTTP,
    ]);

    // Apply additional options
    if (isset($options['headers'])) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $options['headers']);
    }

    if (isset($options['post_data'])) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $options['post_data']);
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);

    curl_close($ch);

    if ($error) {
        throw new Exception("cURL Error: " . $error);
    }

    return [
        'body' => $response,
        'status_code' => $httpCode,
    ];
}

try {
    echo "Setting up Aluvia SDK...\n";
    $sdk = new Aluvia($apiToken);

    // Get a proxy (create one if none exist)
    $proxy = $sdk->first();
    if (!$proxy) {
        echo "No proxies found, creating one...\n";
        $newProxies = $sdk->create(1);
        $proxy = $newProxies[0];
    }

    echo "Using proxy: " . $proxy->getUsername() . "\n";

    // Enable sticky sessions for consistent IP
    $proxy->setUseSticky(true);
    $proxy->save();

    echo "Making test requests...\n\n";

    // Test 1: Check our IP address
    echo "=== Test 1: IP Check ===\n";
    $response = makeProxyRequest($proxy, 'https://httpbin.org/ip');
    echo "Status: " . $response['status_code'] . "\n";
    echo "Response: " . $response['body'] . "\n\n";

    // Test 2: Get headers
    echo "=== Test 2: Headers Check ===\n";
    $response = makeProxyRequest($proxy, 'https://httpbin.org/headers');
    echo "Status: " . $response['status_code'] . "\n";
    $headers = json_decode($response['body'], true);
    if (isset($headers['headers']['X-Forwarded-For'])) {
        echo "Proxy IP detected: " . $headers['headers']['X-Forwarded-For'] . "\n";
    }
    echo "\n";

    // Test 3: Multiple requests with sticky sessions
    echo "=== Test 3: Sticky Session Test ===\n";
    echo "Making 3 consecutive requests to verify same IP...\n";

    $ips = [];
    for ($i = 1; $i <= 3; $i++) {
        $response = makeProxyRequest($proxy, 'https://httpbin.org/ip');
        $data = json_decode($response['body'], true);
        $ip = $data['origin'] ?? 'unknown';
        $ips[] = $ip;
        echo "Request {$i}: {$ip}\n";
        sleep(1); // Small delay between requests
    }

    $uniqueIps = array_unique($ips);
    if (count($uniqueIps) === 1) {
        echo "✅ Sticky sessions working! Same IP for all requests.\n";
    } else {
        echo "⚠️  Different IPs detected. Sticky sessions may not be active.\n";
    }
    echo "\n";

    // Test 4: Custom headers
    echo "=== Test 4: Custom Headers ===\n";
    $customHeaders = [
        'X-Custom-Header: TestValue',
        'Accept: application/json'
    ];

    $response = makeProxyRequest($proxy, 'https://httpbin.org/headers', [
        'headers' => $customHeaders
    ]);

    echo "Status: " . $response['status_code'] . "\n";
    $headerData = json_decode($response['body'], true);
    if (isset($headerData['headers']['X-Custom-Header'])) {
        echo "Custom header received: " . $headerData['headers']['X-Custom-Header'] . "\n";
    }
    echo "\n";

    // Test 5: POST request
    echo "=== Test 5: POST Request ===\n";
    $postData = json_encode([
        'name' => 'Test User',
        'email' => 'test@example.com'
    ]);

    $response = makeProxyRequest($proxy, 'https://httpbin.org/post', [
        'headers' => ['Content-Type: application/json'],
        'post_data' => $postData
    ]);

    echo "Status: " . $response['status_code'] . "\n";
    $postResponse = json_decode($response['body'], true);
    if (isset($postResponse['json'])) {
        echo "Posted data received back: " . json_encode($postResponse['json']) . "\n";
    }
    echo "\n";

    echo "All tests completed successfully!\n";
} catch (AluviaException $e) {
    echo "Aluvia SDK Error: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getErrorCode() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
