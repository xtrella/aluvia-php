# Aluvia PHP SDK

[![Latest Stable Version](https://poser.pugx.org/xtrella/aluvia-php-sdk/v/stable)](https://packagist.org/packages/xtrella/aluvia-php-sdk)
[![Total Downloads](https://poser.pugx.org/xtrella/aluvia-php-sdk/downloads)](https://packagist.org/packages/xtrella/aluvia-php-sdk)
[![License](https://poser.pugx.org/xtrella/aluvia-php-sdk/license)](https://packagist.org/packages/xtrella/aluvia-php-sdk)
[![PHP Version Require](https://poser.pugx.org/xtrella/aluvia-php-sdk/require/php)](https://packagist.org/packages/xtrella/aluvia-php-sdk)

The **official Aluvia PHP SDK**, designed for managing your Aluvia connectivity credentials, retrieving usage data, and integrating mobile network access into PHP applications, backend systems, and automation workflows.

Lightweight, dependency-free, and powered by cURL.

---

## Requirements

- PHP **7.4+**
- cURL extension (enabled by default)

---

## Installation

### Via Composer

```bash
composer require aluvia-connect/aluvia-php-sdk
```

### Manual Installation

```php
require_once 'path/to/aluvia-php-sdk/src/index.php';
```

---

## Quick Start

```php
<?php
use Aluvia\Aluvia;

$sdk = new Aluvia('your-api-token');

// Get your first connectivity credential
$credential = $sdk->first();

if ($credential) {
    echo "Connection URL: " . $credential->toUrl() . "\n";
    echo "Username: " . $credential->getUsername() . "\n";
    echo "Password: " . $credential->getPassword() . "\n";
} else {
    echo "No credentials available. Create one first.\n";
}
```

---

## Usage Examples

---

### Creating Connectivity Credentials

```php
<?php
$sdk = new Aluvia('your-api-token');

// Create a single credential
$credentials = $sdk->create(1);
$credential = $credentials[0];
echo "Created: " . $credential->toUrl() . "\n";

// Create multiple
$list = $sdk->create(5);
echo "Created " . count($list) . " credentials\n";
```

---

### Managing Credentials

```php
<?php
$sdk = new Aluvia('your-api-token');

$credential = $sdk->find('user123');

if ($credential) {
    // Modify settings
    $credential->setUseSticky(true);

    // Save changes
    $credential->save();

    echo "Credential updated.\n";
} else {
    echo "Credential not found.\n";
}
```

---

### Usage Statistics

```php
<?php
$sdk = new Aluvia('your-api-token');
$credential = $sdk->first();

if ($credential) {
    // Current period usage
    $usage = $credential->getUsage();
    echo "Used: " . $usage['dataUsed'] . " GB\n";

    // Custom date range
    $weekly = $sdk->getUsage($credential->getUsername(), [
        'usageStart' => time() - (7 * 86400),
        'usageEnd'   => time(),
    ]);

    echo "Weekly usage: " . $weekly['dataUsed'] . " GB\n";
}
```

---

### Connection URLs & Details

```php
<?php
$credential = $sdk->first();

if ($credential) {
    echo "HTTP: " . $credential->toUrl('http') . "\n";
    echo "HTTPS: " . $credential->toUrl('https') . "\n";
    print_r($credential->toArray());
}
```

---

### Using with cURL

```php
<?php
$credential = $sdk->first();

if ($credential) {
    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://ipconfig.io/json',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_PROXY => $credential->getHost() . ':' . $credential->getHttpPort(),
        CURLOPT_PROXYUSERPWD => $credential->getUsername() . ':' . $credential->getPassword(),
        CURLOPT_PROXYTYPE => CURLPROXY_HTTP,
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    echo "Response: $response\n";
}
```

---

### Deleting Credentials

```php
<?php
$credential = $sdk->find('user123');

if ($credential) {
    $credential->delete();
    echo "Credential deleted.\n";
}

$sdk->delete('anotherUser');
```

---

## Error Handling

```php
<?php
use Aluvia\Exceptions\AuthenticationException;
use Aluvia\Exceptions\ValidationException;
use Aluvia\Exceptions\NetworkException;
use Aluvia\Exceptions\ApiException;
use Aluvia\Exceptions\RateLimitException;

try {
    $credential = $sdk->first();
} catch (AuthenticationException $e) {
    echo "Auth error: " . $e->getMessage();
} catch (RateLimitException $e) {
    echo "Rate limited. Retry in " . $e->getRetryAfter() . "s";
} catch (NetworkException $e) {
    echo "Network issue: " . $e->getMessage();
} catch (ApiException $e) {
    echo "API error: " . $e->getMessage();
} catch (ValidationException $e) {
    echo "Invalid request: " . $e->getMessage();
}
```

---

# API Reference

## Aluvia Class

### Constructor

```
new Aluvia(string $token)
```

### Methods

| Method                                                   | Description                                 |
| -------------------------------------------------------- | ------------------------------------------- |
| `first(): ?Proxy`                                        | Get the most recent connectivity credential |
| `find(string $username): ?Proxy`                         | Find a credential                           |
| `create(int $count = 1): Proxy[]`                        | Create credentials                          |
| `update(string $username, array $options): void`         | Update credential settings                  |
| `delete(string $username): void`                         | Delete credential                           |
| `all(): Proxy[]`                                         | List all loaded credentials                 |
| `getUsage(string $username, array $options = []): array` | Usage analytics                             |

---

## Proxy Class (Connectivity Credential)

### Properties & Accessors

- `getUsername(): string`
- `getPassword(): string`
- `getHost(): string`
- `getHttpPort(): int`
- `getHttpsPort(): int`
- `getUseSticky(): bool`
- `setUseSticky(bool $value)`

### Methods

- `toUrl(string $protocol = 'http'): string`
- `save(): self`
- `delete(): void`
- `getUsage(array $options = []): array`
- `toArray(): array`

---

## License

MIT License

---

## Support

For questions or support: **[support@aluvia.io](mailto:support@aluvia.io)**
