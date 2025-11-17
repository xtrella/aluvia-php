# Aluvia PHP SDK

[![Latest Stable Version](https://poser.pugx.org/xtrella/aluvia-php-sdk/v/stable)](https://packagist.org/packages/xtrella/aluvia-php-sdk)
[![Total Downloads](https://poser.pugx.org/xtrella/aluvia-php-sdk/downloads)](https://packagist.org/packages/xtrella/aluvia-php-sdk)
[![License](https://poser.pugx.org/xtrella/aluvia-php-sdk/license)](https://packagist.org/packages/xtrella/aluvia-php-sdk)
[![PHP Version Require](https://poser.pugx.org/xtrella/aluvia-php-sdk/require/php)](https://packagist.org/packages/xtrella/aluvia-php-sdk)
[![CI/CD Pipeline](https://github.com/xtrella/aluvia-php/actions/workflows/ci-cd.yml/badge.svg)](https://github.com/xtrella/aluvia-php/actions/workflows/ci-cd.yml)

Official PHP SDK for the Aluvia proxy management API. This lightweight SDK provides easy access to Aluvia's proxy services with no external dependencies.

## Requirements

- PHP 7.4 or higher
- cURL extension (usually enabled by default)

## Installation

### Using Composer

```bash
composer require xtrella/aluvia-php-sdk
```

### Manual Installation

1. Download the SDK files
2. Include the main index file in your project:

```php
require_once 'path/to/aluvia-php-sdk/src/index.php';
```

## Quick Start

```php
<?php
use Aluvia\Aluvia;

// Create SDK instance with your API token
$sdk = new Aluvia('your-api-token');

// Get your first available proxy
$proxy = $sdk->first();

if ($proxy) {
    echo "Proxy URL: " . $proxy->toUrl() . "\n";
    echo "Username: " . $proxy->getUsername() . "\n";
    echo "Password: " . $proxy->getPassword() . "\n";
} else {
    echo "No proxies available, create one first\n";
}
```

## Usage Examples

### Creating Proxies

```php
<?php
use Aluvia\Aluvia;

$sdk = new Aluvia('your-api-token');

// Create a single proxy
$proxies = $sdk->create(1);
$proxy = $proxies[0];

// Create multiple proxies
$newProxies = $sdk->create(5);
echo "Created " . count($newProxies) . " new proxies\n";
```

### Finding and Managing Proxies

```php
<?php
use Aluvia\Aluvia;

$sdk = new Aluvia('your-api-token');

// Find a specific proxy by username
$proxy = $sdk->find('user123');

if ($proxy) {
    // Enable sticky sessions
    $proxy->setUseSticky(true);

    // Save changes to server
    $proxy->save();

    echo "Updated proxy settings\n";
}

// Get all proxies
$allProxies = $sdk->all();
foreach ($allProxies as $proxy) {
    echo "Proxy: " . $proxy->getUsername() . "\n";
}
```

### Usage Statistics

```php
<?php
use Aluvia\Aluvia;

$sdk = new Aluvia('your-api-token');

$proxy = $sdk->first();
if ($proxy) {
    // Get current usage
    $usage = $proxy->getUsage();
    echo "Data used: " . $usage['dataUsed'] . " GB\n";

    // Get usage for specific date range
    $customUsage = $sdk->getUsage($proxy->getUsername(), [
        'usageStart' => time() - (7 * 24 * 60 * 60), // 7 days ago
        'usageEnd' => time()
    ]);
    echo "Weekly usage: " . $customUsage['dataUsed'] . " GB\n";
}
```

### Proxy URLs and Authentication

```php
<?php
use Aluvia\Aluvia;

$sdk = new Aluvia('your-api-token');

$proxy = $sdk->first();
if ($proxy) {
    // Get HTTP proxy URL
    $httpUrl = $proxy->toUrl('http');
    echo "HTTP Proxy: $httpUrl\n";

    // Get HTTPS proxy URL
    $httpsUrl = $proxy->toUrl('https');
    echo "HTTPS Proxy: $httpsUrl\n";

    // Get proxy details as array
    $details = $proxy->toArray();
    print_r($details);
}
```

### Using with cURL

```php
<?php
use Aluvia\Aluvia;

$sdk = new Aluvia('your-api-token');
$proxy = $sdk->first();

if ($proxy) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://httpbin.org/ip',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_PROXY => $proxy->getHost() . ':' . $proxy->getHttpPort(),
        CURLOPT_PROXYUSERPWD => $proxy->getUsername() . ':' . $proxy->getPassword(),
        CURLOPT_PROXYTYPE => CURLPROXY_HTTP,
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    echo "Response: $response\n";
}
```

### Deleting Proxies

```php
<?php
use Aluvia\Aluvia;

$sdk = new Aluvia('your-api-token');

// Delete a specific proxy
$proxy = $sdk->find('user123');
if ($proxy) {
    $proxy->delete();
    echo "Proxy deleted successfully\n";
}

// Or delete directly by username
$sdk->delete('user456');
```

## Error Handling

The SDK uses typed exceptions for different error conditions:

```php
<?php
use Aluvia\Aluvia;
use Aluvia\Exceptions\AuthenticationException;
use Aluvia\Exceptions\NetworkException;
use Aluvia\Exceptions\ApiException;
use Aluvia\Exceptions\ValidationException;
use Aluvia\Exceptions\RateLimitException;

$sdk = new Aluvia('your-api-token');

try {
    $proxy = $sdk->first();
} catch (AuthenticationException $e) {
    echo "Authentication failed: " . $e->getMessage() . "\n";
} catch (NetworkException $e) {
    echo "Network error: " . $e->getMessage() . "\n";
} catch (RateLimitException $e) {
    echo "Rate limited. Retry after: " . $e->getRetryAfter() . " seconds\n";
} catch (ApiException $e) {
    echo "API error: " . $e->getMessage() . " (Status: " . $e->getStatusCode() . ")\n";
} catch (ValidationException $e) {
    echo "Validation error: " . $e->getMessage() . "\n";
    print_r($e->getDetails());
}
```

## API Reference

### Aluvia Class

#### Constructor

- `new Aluvia(string $token)` - Create SDK instance with API token

#### Methods

- `first(): ?Proxy` - Get the most recently created proxy
- `find(string $username): ?Proxy` - Find proxy by username
- `create(int $count = 1): Proxy[]` - Create new proxies
- `update(string $username, array $options): void` - Update proxy settings
- `delete(string $username): void` - Delete proxy
- `all(): Proxy[]` - Get all loaded proxies
- `getUsage(string $username, array $options = []): array` - Get usage statistics

### Proxy Class

#### Methods

- `getUsername(): string` - Get proxy username
- `getPassword(): string` - Get proxy password
- `getHost(): string` - Get proxy host
- `getHttpPort(): int` - Get HTTP port
- `getHttpsPort(): int` - Get HTTPS port
- `getUseSticky(): bool` - Check if sticky sessions enabled
- `setUseSticky(bool $useSticky): void` - Enable/disable sticky sessions
- `toUrl(string $protocol = 'http'): string` - Generate proxy URL
- `getUsage(array $options = []): array` - Get usage statistics
- `save(): self` - Save changes to server
- `delete(): void` - Delete this proxy
- `toArray(): array` - Convert to array

## License

MIT License. See LICENSE file for details.

## Support

For support and questions, contact: support@xtrella.com
