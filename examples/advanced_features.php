<?php

/**
 * Advanced Features Example
 *
 * This example demonstrates advanced SDK features like bulk operations,
 * error handling, and proxy management
 */

require_once '../src/index.php';

use Aluvia\Aluvia;
use Aluvia\Exceptions\AluviaException;
use Aluvia\Exceptions\RateLimitException;
use Aluvia\Exceptions\AuthenticationException;

// Replace with your actual API token
$apiToken = 'your-api-token-here';

try {
    echo "=== Advanced Aluvia SDK Features Demo ===\n\n";

    $sdk = new Aluvia($apiToken);

    // 1. Bulk proxy creation
    echo "1. Creating multiple proxies...\n";
    try {
        $newProxies = $sdk->create(3);
        echo "✅ Created " . count($newProxies) . " proxies successfully!\n";

        foreach ($newProxies as $i => $proxy) {
            echo "   Proxy " . ($i + 1) . ": " . $proxy->getUsername() . "\n";
        }
    } catch (AluviaException $e) {
        echo "❌ Failed to create proxies: " . $e->getMessage() . "\n";
    }
    echo "\n";

    // 2. Get all proxies and manage them
    echo "2. Managing all proxies...\n";
    $allProxies = $sdk->all();
    echo "Total proxies in account: " . count($allProxies) . "\n";

    foreach ($allProxies as $i => $proxy) {
        echo "   Proxy " . ($i + 1) . ":\n";
        echo "     Username: " . $proxy->getUsername() . "\n";
        echo "     Sticky: " . ($proxy->getUseSticky() ? 'Yes' : 'No') . "\n";
        echo "     Smart Routing: " . ($proxy->getUseSmartRouting() ? 'Yes' : 'No') . "\n";
    }
    echo "\n";

    // 3. Configure proxy features
    echo "3. Configuring proxy features...\n";
    if (!empty($allProxies)) {
        $firstProxy = $allProxies[0];

        echo "Configuring proxy: " . $firstProxy->getUsername() . "\n";

        // Enable both sticky sessions and smart routing
        $firstProxy->setUseSticky(true);
        $firstProxy->setUseSmartRouting(true);
        $firstProxy->save();

        echo "✅ Enabled sticky sessions and smart routing\n";
        echo "Updated URL: " . $firstProxy->toUrl() . "\n";
    }
    echo "\n";

    // 4. Usage monitoring
    echo "4. Usage monitoring...\n";
    if (!empty($allProxies)) {
        foreach (array_slice($allProxies, 0, 2) as $proxy) { // Check first 2 proxies
            try {
                $usage = $proxy->getUsage();
                echo "Proxy " . $proxy->getUsername() . ":\n";
                echo "   Period: " . date('Y-m-d', $usage['usageStart']) . " to " . date('Y-m-d', $usage['usageEnd']) . "\n";
                echo "   Data Used: " . number_format($usage['dataUsed'], 2) . " GB\n";

                // Get weekly usage
                $weeklyUsage = $sdk->getUsage($proxy->getUsername(), [
                    'usageStart' => time() - (7 * 24 * 60 * 60),
                    'usageEnd' => time()
                ]);
                echo "   Weekly Usage: " . number_format($weeklyUsage['dataUsed'], 2) . " GB\n";
            } catch (AluviaException $e) {
                echo "❌ Failed to get usage for " . $proxy->getUsername() . ": " . $e->getMessage() . "\n";
            }
        }
    }
    echo "\n";

    // 5. Proxy search and update
    echo "5. Proxy search and update...\n";
    if (!empty($allProxies)) {
        $targetUsername = $allProxies[0]->getUsername();

        // Find specific proxy
        $foundProxy = $sdk->find($targetUsername);
        if ($foundProxy) {
            echo "✅ Found proxy: " . $foundProxy->getUsername() . "\n";

            // Update using SDK method
            $sdk->update($targetUsername, [
                'useSticky' => false,
                'useSmartRouting' => true
            ]);
            echo "✅ Updated proxy configuration via SDK\n";

            // Verify the update
            $updatedProxy = $sdk->find($targetUsername);
            if ($updatedProxy) {
                echo "Verified - Sticky: " . ($updatedProxy->getUseSticky() ? 'Yes' : 'No') .
                    ", Smart Routing: " . ($updatedProxy->getUseSmartRouting() ? 'Yes' : 'No') . "\n";
            }
        }
    }
    echo "\n";

    // 6. Error handling demonstration
    echo "6. Error handling demonstration...\n";

    // Try to find non-existent proxy
    $nonExistentProxy = $sdk->find('non-existent-proxy-12345');
    if ($nonExistentProxy === null) {
        echo "✅ Correctly handled non-existent proxy (returned null)\n";
    }

    // Try to create with invalid count (this should fail)
    try {
        $sdk->create(0); // Invalid count
        echo "❌ Should have failed with invalid count\n";
    } catch (AluviaException $e) {
        echo "✅ Correctly caught validation error: " . $e->getMessage() . "\n";
    }

    echo "\n";

    // 7. Proxy array conversion
    echo "7. Proxy serialization...\n";
    if (!empty($allProxies)) {
        $proxy = $allProxies[0];
        $proxyArray = $proxy->toArray();

        echo "Proxy as array:\n";
        echo json_encode($proxyArray, JSON_PRETTY_PRINT) . "\n";
    }
    echo "\n";

    // 8. Cleanup (optional - uncomment to delete test proxies)
    /*
    echo "8. Cleanup - deleting test proxies...\n";
    $proxiesToDelete = array_slice($allProxies, -2); // Delete last 2 proxies

    foreach ($proxiesToDelete as $proxy) {
        try {
            $username = $proxy->getUsername();
            $proxy->delete();
            echo "✅ Deleted proxy: {$username}\n";
        } catch (AluviaException $e) {
            echo "❌ Failed to delete proxy: " . $e->getMessage() . "\n";
        }
    }
    */

    echo "=== Demo completed successfully! ===\n";
} catch (RateLimitException $e) {
    echo "❌ Rate limit exceeded. Please wait " . $e->getRetryAfter() . " seconds and try again.\n";
} catch (AuthenticationException $e) {
    echo "❌ Authentication failed. Please check your API token.\n";
    echo "Details: " . $e->getMessage() . "\n";
} catch (AluviaException $e) {
    echo "❌ Aluvia SDK Error: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getErrorCode() . "\n";

    if ($e->getDetails()) {
        echo "Details: " . json_encode($e->getDetails(), JSON_PRETTY_PRINT) . "\n";
    }
} catch (Exception $e) {
    echo "❌ Unexpected error: " . $e->getMessage() . "\n";
}
