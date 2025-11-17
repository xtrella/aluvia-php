<?php

declare(strict_types=1);

namespace Aluvia;

/**
 * Represents the authentication credentials for a proxy connection
 */
class ProxyCredential
{
    public string $username;
    public string $password;
    public bool $useSticky;
    public ?string $sessionSalt;

    public function __construct(
        string $username,
        string $password,
        bool $useSticky = false,
        ?string $sessionSalt = null
    ) {
        $this->username = $username;
        $this->password = $password;
        $this->useSticky = $useSticky;
        $this->sessionSalt = $sessionSalt;
    }
}

/**
 * Configuration settings for proxy server connection
 */
class ProxyConfig
{
    public string $host;
    public int $httpPort;
    public int $httpsPort;

    public function __construct(
        string $host = 'proxy.aluvia.io',
        int $httpPort = 8080,
        int $httpsPort = 8443
    ) {
        $this->host = $host;
        $this->httpPort = $httpPort;
        $this->httpsPort = $httpsPort;
    }
}

/**
 * Represents a single proxy instance with authentication and configuration
 */
class Proxy
{
    private ProxyCredential $credential;
    private ProxyConfig $config;
    private Aluvia $sdk;

    public function __construct(ProxyCredential $credential, ProxyConfig $config, Aluvia $sdk)
    {
        $this->credential = $credential;
        $this->config = $config;
        $this->sdk = $sdk;
    }

    /**
     * Get the username for this proxy
     */
    public function getUsername(): string
    {
        return $this->credential->username;
    }

    /**
     * Get the password for this proxy
     */
    public function getPassword(): string
    {
        return $this->credential->password;
    }

    /**
     * Get the proxy host
     */
    public function getHost(): string
    {
        return $this->config->host;
    }

    /**
     * Get the HTTP port
     */
    public function getHttpPort(): int
    {
        return $this->config->httpPort;
    }

    /**
     * Get the HTTPS port
     */
    public function getHttpsPort(): int
    {
        return $this->config->httpsPort;
    }

    /**
     * Check if sticky sessions are enabled
     */
    public function getUseSticky(): bool
    {
        return $this->credential->useSticky;
    }

    /**
     * Set sticky sessions enabled/disabled
     */
    public function setUseSticky(bool $useSticky): void
    {
        $this->credential->useSticky = $useSticky;
    }



    /**
     * Generate a proxy URL for HTTP or HTTPS connections
     *
     * @param string $protocol Either 'http' or 'https'
     * @return string The complete proxy URL
     */
    public function toUrl(string $protocol = 'http'): string
    {
        $builtCredential = $this->buildCredential();
        $port = $protocol === 'https' ? $this->config->httpsPort : $this->config->httpPort;

        return "{$protocol}://{$builtCredential->username}:{$builtCredential->password}@{$this->config->host}:{$port}";
    }

    /**
     * Retrieve detailed usage information for this proxy
     *
     * @param array $options Optional date range filtering
     * @return array Usage information
     */
    public function getUsage(array $options = []): array
    {
        return $this->sdk->getUsage($this->credential->username, $options);
    }

    /**
     * Save any changes made to the proxy configuration to the server
     *
     * @return self The updated Proxy instance
     */
    public function save(): self
    {
        // Generate new session salt every time sticky is enabled
        if ($this->credential->useSticky) {
            $this->credential->sessionSalt = $this->generateSessionSalt();
        }

        // Clear session salt when disabling sticky sessions
        if (!$this->credential->useSticky) {
            $this->credential->sessionSalt = null;
        }

        $this->sdk->update($this->credential->username, [
            'useSticky' => $this->credential->useSticky,
        ]);

        return $this;
    }

    /**
     * Delete this proxy from your Aluvia account
     */
    public function delete(): void
    {
        $this->sdk->delete($this->credential->username);
    }

    /**
     * Convert the proxy instance to an associative array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'username' => $this->getUsername(),
            'password' => $this->getPassword(),
            'host' => $this->getHost(),
            'httpPort' => $this->getHttpPort(),
            'httpsPort' => $this->getHttpsPort(),
            'useSticky' => $this->getUseSticky(),
        ];
    }

    /**
     * Build credential with proper username formatting
     */
    private function buildCredential(): ProxyCredential
    {
        $username = $this->stripUsernameSuffixes($this->credential->username);

        // Add sticky session suffix
        if ($this->credential->useSticky && $this->credential->sessionSalt) {
            $username .= "-session-{$this->credential->sessionSalt}";
        }

        return new ProxyCredential(
            $username,
            $this->credential->password,
            $this->credential->useSticky,
            $this->credential->sessionSalt
        );
    }

    /**
     * Generate random session salt
     */
    private function generateSessionSalt(int $length = 8): string
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $result = '';

        for ($i = 0; $i < $length; $i++) {
            $result .= $characters[random_int(0, strlen($characters) - 1)];
        }

        return $result;
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
