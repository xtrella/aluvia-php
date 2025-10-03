<?php

/**
 * Basic Usage Example
 *
 * This example shows the most common operations with the Aluvia PHP SDK
 */

require_once '../src/index.php';

use Aluvia\Aluvia;
use Aluvia\Exceptions\AluviaException;

// Replace with your actual API token
$apiToken = 'your-api-token-here';

try {
    // Create SDK instance
    echo "Creating Aluvia SDK instance...\n";
    $sdk = new Aluvia($apiToken);

    // Get first available proxy
    echo "Getting first available proxy...\n";
    $proxy = $sdk->first();

    if ($proxy) {
        echo "Found proxy:\n";
        echo "- Username: " . $proxy->getUsername() . "\n";
        echo "- Host: " . $proxy->getHost() . "\n";
        echo "- HTTP Port: " . $proxy->getHttpPort() . "\n";
        echo "- HTTPS Port: " . $proxy->getHttpsPort() . "\n";
        echo "- HTTP URL: " . $proxy->toUrl('http') . "\n";
        echo "- HTTPS URL: " . $proxy->toUrl('https') . "\n";

        // Get usage information
        echo "\nGetting usage information...\n";
        $usage = $proxy->getUsage();
        echo "- Usage Period: " . date('Y-m-d', $usage['usageStart']) . " to " . date('Y-m-d', $usage['usageEnd']) . "\n";
        echo "- Data Used: " . $usage['dataUsed'] . " GB\n";

        // Enable sticky sessions
        echo "\nEnabling sticky sessions...\n";
        $proxy->setUseSticky(true);
        $proxy->save();
        echo "Sticky sessions enabled!\n";

        // Show updated URL with session salt
        echo "Updated proxy URL: " . $proxy->toUrl() . "\n";
    } else {
        echo "No proxies found. Let's create one...\n";

        // Create a new proxy
        $newProxies = $sdk->create(1);
        $proxy = $newProxies[0];

        echo "Created new proxy: " . $proxy->getUsername() . "\n";
        echo "Proxy URL: " . $proxy->toUrl() . "\n";
    }

    // Get all proxies
    echo "\nGetting all proxies...\n";
    $allProxies = $sdk->all();
    echo "Total proxies in account: " . count($allProxies) . "\n";
} catch (AluviaException $e) {
    echo "Aluvia SDK Error: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getErrorCode() . "\n";

    if ($e->getDetails()) {
        echo "Details: " . json_encode($e->getDetails(), JSON_PRETTY_PRINT) . "\n";
    }
} catch (Exception $e) {
    echo "General Error: " . $e->getMessage() . "\n";
}
