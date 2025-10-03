<?php

declare(strict_types=1);

namespace Aluvia\Http;

use Aluvia\Exceptions\NetworkException;

/**
 * HTTP response class
 */
class Response
{
    private int $statusCode;
    private array $headers;
    private string $body;

    public function __construct(int $statusCode, array $headers, string $body)
    {
        $this->statusCode = $statusCode;
        $this->headers = $headers;
        $this->body = $body;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getHeader(string $name): ?string
    {
        $normalized = strtolower($name);
        foreach ($this->headers as $headerName => $headerValue) {
            if (strtolower($headerName) === $normalized) {
                return $headerValue;
            }
        }
        return null;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function isSuccessful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    /**
     * Parse JSON response body
     *
     * @return mixed
     * @throws NetworkException
     */
    public function json()
    {
        $decoded = json_decode($this->body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new NetworkException(
                'Failed to parse JSON response: ' . json_last_error_msg(),
                [
                    'body' => $this->body,
                    'error' => json_last_error_msg(),
                ]
            );
        }
        return $decoded;
    }
}

/**
 * Simple HTTP client using cURL (no external dependencies)
 */
class HttpClient
{
    private array $defaultHeaders;
    private int $timeout;
    private int $connectTimeout;

    public function __construct(array $defaultHeaders = [], int $timeout = 30, int $connectTimeout = 10)
    {
        $this->defaultHeaders = $defaultHeaders;
        $this->timeout = $timeout;
        $this->connectTimeout = $connectTimeout;

        if (!extension_loaded('curl')) {
            throw new NetworkException('cURL extension is required but not loaded');
        }
    }

    /**
     * Make an HTTP request
     *
     * @param string $method HTTP method
     * @param string $url Request URL
     * @param array $headers Additional headers
     * @param string|null $body Request body
     * @return Response
     * @throws NetworkException
     */
    public function request(string $method, string $url, array $headers = [], ?string $body = null): Response
    {
        $ch = curl_init();
        if ($ch === false) {
            throw new NetworkException('Failed to initialize cURL');
        }

        try {
            // Merge headers
            $allHeaders = array_merge($this->defaultHeaders, $headers);
            $curlHeaders = [];
            foreach ($allHeaders as $name => $value) {
                $curlHeaders[] = "{$name}: {$value}";
            }

            // Configure cURL options
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => true,
                CURLOPT_TIMEOUT => $this->timeout,
                CURLOPT_CONNECTTIMEOUT => $this->connectTimeout,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 5,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_USERAGENT => 'Aluvia-PHP-SDK/1.0',
                CURLOPT_HTTPHEADER => $curlHeaders,
            ]);

            // Set method-specific options
            switch (strtoupper($method)) {
                case 'GET':
                    curl_setopt($ch, CURLOPT_HTTPGET, true);
                    break;
                case 'POST':
                    curl_setopt($ch, CURLOPT_POST, true);
                    if ($body !== null) {
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
                    }
                    break;
                case 'PUT':
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                    if ($body !== null) {
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
                    }
                    break;
                case 'DELETE':
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                    if ($body !== null) {
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
                    }
                    break;
                case 'PATCH':
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
                    if ($body !== null) {
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
                    }
                    break;
                default:
                    throw new NetworkException("Unsupported HTTP method: {$method}");
            }

            $response = curl_exec($ch);

            if ($response === false) {
                $error = curl_error($ch);
                $errno = curl_errno($ch);
                throw new NetworkException(
                    "cURL error ({$errno}): {$error}",
                    [
                        'url' => $url,
                        'method' => $method,
                        'curl_error' => $error,
                        'curl_errno' => $errno,
                    ]
                );
            }

            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

            $headerString = substr($response, 0, $headerSize);
            $body = substr($response, $headerSize);

            $headers = $this->parseHeaders($headerString);

            return new Response($statusCode, $headers, $body);
        } finally {
            curl_close($ch);
        }
    }

    /**
     * Parse HTTP headers from response
     *
     * @param string $headerString
     * @return array
     */
    private function parseHeaders(string $headerString): array
    {
        $headers = [];
        $lines = explode("\r\n", $headerString);

        foreach ($lines as $line) {
            if (strpos($line, ':') !== false) {
                [$name, $value] = explode(':', $line, 2);
                $headers[trim($name)] = trim($value);
            }
        }

        return $headers;
    }

    public function get(string $url, array $headers = []): Response
    {
        return $this->request('GET', $url, $headers);
    }

    public function post(string $url, ?string $body = null, array $headers = []): Response
    {
        return $this->request('POST', $url, $headers, $body);
    }

    public function put(string $url, ?string $body = null, array $headers = []): Response
    {
        return $this->request('PUT', $url, $headers, $body);
    }

    public function delete(string $url, ?string $body = null, array $headers = []): Response
    {
        return $this->request('DELETE', $url, $headers, $body);
    }

    public function patch(string $url, ?string $body = null, array $headers = []): Response
    {
        return $this->request('PATCH', $url, $headers, $body);
    }
}
