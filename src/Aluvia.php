<?php

declare(strict_types=1);

namespace Aluvia;

use Aluvia\Exceptions\ApiException;

/**
 * The main Aluvia SDK client for managing proxy connections
 */
class Aluvia
{
    /** SDK version for tracking and debugging */
    public const VERSION = '1.0.0';

    private ProxyConfig $config;
    private array $credentials = [];
    private string $token;
    private ApiClient $api;

    /**
     * Create a new Aluvia SDK instance
     *
     * @param string $token Your Aluvia API authentication token
     * @throws \Aluvia\Exceptions\ValidationException When token is invalid
     */
    public function __construct(string $token)
    {
        $this->token = Validator::validateApiToken($token);
        $this->config = new ProxyConfig();
        $this->api = $GLOBALS['aluvia_api'];
    }

    /**
     * Parse options from API response
     */
    private function parseOptions(?array $options): array
    {
        return [
            'useSticky' => isset($options['use_sticky']) ? (bool)$options['use_sticky'] : false,
        ];
    }

    /**
     * Get the most recently created proxy from your account
     *
     * @return Proxy|null A Proxy instance or null if no proxies exist
     */
    public function first(): ?Proxy
    {
        $this->initCredentials();

        if (!empty($this->credentials)) {
            return new Proxy($this->credentials[0], $this->config, $this);
        }

        return null;
    }

    /**
     * Initialize credentials from API
     */
    private function initCredentials(): void
    {
        if (!empty($this->credentials)) {
            return;
        }

        $headers = ['Authorization' => "Bearer {$this->token}"];
        $response = $this->api->get('/credentials', $headers);

        if (!isset($response['success']) || !$response['success']) {
            throw new ApiException($response['message'] ?? 'Failed to load credentials');
        }

        $this->credentials = array_map(function ($cred) {
            $options = $this->parseOptions($cred['options'] ?? null);
            return new ProxyCredential(
                $cred['username'],
                $cred['password'],
                $options['useSticky']
            );
        }, $response['data'] ?? []);
    }

    /**
     * Find and return a specific proxy by its username
     *
     * @param string $username The base username of the proxy to find
     * @return Proxy|null A Proxy instance or null if not found
     */
    public function find(string $username): ?Proxy
    {
        try {
            $baseUsername = $this->stripUsernameSuffixes($username);

            // Check local cache first
            foreach ($this->credentials as $cred) {
                if ($this->stripUsernameSuffixes($cred->username) === $baseUsername) {
                    return new Proxy($cred, $this->config, $this);
                }
            }

            $headers = ['Authorization' => "Bearer {$this->token}"];
            $response = $this->api->get("/credentials/{$baseUsername}", $headers);

            if (!isset($response['success']) || !$response['success']) {
                return null;
            }

            $options = $this->parseOptions($response['data']['options'] ?? null);
            $credential = new ProxyCredential(
                $response['data']['username'],
                $response['data']['password'],
                $options['useSticky']
            );

            $this->credentials[] = $credential;
            return new Proxy($credential, $this->config, $this);
        } catch (ApiException $error) {
            if ($error->getStatusCode() === 404) {
                return null; // Proxy not found is not an error condition
            }
            throw $error;
        }
    }

    /**
     * Create new proxy instances in your Aluvia account
     *
     * @param int $count The number of proxies to create (default: 1)
     * @return Proxy[] Array of newly created Proxy instances
     */
    public function create(int $count = 1): array
    {
        $validCount = Validator::validateProxyCount($count);

        $headers = ['Authorization' => "Bearer {$this->token}"];
        $data = ['count' => $validCount];

        $response = $this->api->post('/credentials', $data, $headers);

        if (!isset($response['success']) || !$response['success']) {
            throw new ApiException($response['message'] ?? 'Failed to create proxies');
        }

        $newProxies = [];
        foreach ($response['data'] as $credData) {
            $options = $this->parseOptions($credData['options'] ?? null);
            $credential = new ProxyCredential(
                $credData['username'],
                $credData['password'],
                $options['useSticky']
            );

            $this->credentials[] = $credential;
            $newProxies[] = new Proxy($credential, $this->config, $this);
        }

        return $newProxies;
    }

    /**
     * Update an existing proxy's configuration
     *
     * @param string $username The username of the proxy to update
     * @param array $options The options to update
     */
    public function update(string $username, array $options): void
    {
        $validUsername = Validator::validateUsername($username);
        $baseUsername = $this->stripUsernameSuffixes($validUsername);

        $headers = ['Authorization' => "Bearer {$this->token}"];
        $data = [
            'options' => [
                'use_sticky' => $options['useSticky'] ?? false,
            ]
        ];

        $response = $this->api->put("/credentials/{$baseUsername}", $data, $headers);

        if (!isset($response['success']) || !$response['success']) {
            throw new ApiException($response['message'] ?? 'Failed to update proxy');
        }

        // Update local cache
        foreach ($this->credentials as $cred) {
            if ($this->stripUsernameSuffixes($cred->username) === $baseUsername) {
                $cred->useSticky = $options['useSticky'] ?? $cred->useSticky;
                break;
            }
        }
    }

    /**
     * Delete a proxy from your Aluvia account
     *
     * @param string $username The username of the proxy to delete
     */
    public function delete(string $username): void
    {
        $validUsername = Validator::validateUsername($username);
        $baseUsername = $this->stripUsernameSuffixes($validUsername);

        $headers = ['Authorization' => "Bearer {$this->token}"];
        $response = $this->api->delete("/credentials/{$baseUsername}", $headers);

        if (!isset($response['success']) || !$response['success']) {
            throw new ApiException($response['message'] ?? 'Failed to delete proxy');
        }

        // Remove from local cache
        $this->credentials = array_filter($this->credentials, function ($cred) use ($baseUsername) {
            return $this->stripUsernameSuffixes($cred->username) !== $baseUsername;
        });
        $this->credentials = array_values($this->credentials); // Re-index
    }

    /**
     * Return all currently loaded proxy instances
     *
     * @return Proxy[] Array of all currently loaded Proxy instances
     */
    public function all(): array
    {
        $this->initCredentials();
        return array_map(function ($cred) {
            return new Proxy($cred, $this->config, $this);
        }, $this->credentials);
    }

    /**
     * Get detailed usage information for a specific proxy
     *
     * @param string $username The username of the proxy to get usage for
     * @param array $options Optional date range filtering
     * @return array Usage information
     */
    public function getUsage(string $username, array $options = []): array
    {
        $validUsername = Validator::validateUsername($username);
        $baseUsername = $this->stripUsernameSuffixes($validUsername);
        $headers = ['Authorization' => "Bearer {$this->token}"];

        $queryParams = [];
        if (isset($options['usageStart'])) {
            $queryParams['usage_start'] = (string)$options['usageStart'];
        }
        if (isset($options['usageEnd'])) {
            $queryParams['usage_end'] = (string)$options['usageEnd'];
        }

        $endpoint = "/credentials/{$baseUsername}";
        if (!empty($queryParams)) {
            $endpoint .= '?' . http_build_query($queryParams);
        }

        $response = $this->api->get($endpoint, $headers);

        if (isset($response['success']) && $response['success']) {
            return [
                'usageStart' => $response['data']['usage_start'],
                'usageEnd' => $response['data']['usage_end'],
                'dataUsed' => $response['data']['data_used'],
            ];
        }

        throw new ApiException($response['message'] ?? 'Failed to get proxy usage');
    }

    /**
     * Strip session and routing suffixes from username
     */
    private function stripUsernameSuffixes(string $username): string
    {
        $username = preg_replace('/-session-[a-zA-Z0-9]+/', '', $username);
        return $username;
    }
}
