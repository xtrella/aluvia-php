<?php

declare(strict_types=1);

namespace Aluvia;

use Aluvia\Http\HttpClient;
use Aluvia\Http\Response;
use Aluvia\Exceptions\ApiException;
use Aluvia\Exceptions\AuthenticationException;
use Aluvia\Exceptions\NetworkException;
use Aluvia\Exceptions\RateLimitException;

/**
 * The default Aluvia API endpoint
 */
const API_ORIGIN = 'https://api.aluvia.io';

/**
 * Universal HTTP client for Aluvia API requests
 */
class ApiClient
{
    private string $baseURL;
    private HttpClient $httpClient;

    public function __construct(string $baseURL = API_ORIGIN)
    {
        $this->baseURL = $baseURL;
        $this->httpClient = new HttpClient([
            'Content-Type' => 'application/json'
        ]);
    }

    /**
     * Make a request to the API
     *
     * @param string $endpoint
     * @param string $method
     * @param array $headers
     * @param mixed $data
     * @return mixed
     * @throws ApiException|AuthenticationException|NetworkException|RateLimitException
     */
    public function request(string $endpoint, string $method = 'GET', array $headers = [], $data = null)
    {
        try {
            $url = $this->baseURL . $endpoint;
            $body = $data ? json_encode($data) : null;

            $response = $this->httpClient->request($method, $url, $headers, $body);

            if (!$response->isSuccessful()) {
                $this->handleErrorResponse($response);
            }

            return $response->json();
        } catch (\Exception $error) {
            if (
                !($error instanceof ApiException) &&
                !($error instanceof AuthenticationException) &&
                !($error instanceof RateLimitException)
            ) {
                // Network or other errors
                throw new NetworkException(
                    'Request failed: ' . $error->getMessage(),
                    [
                        'endpoint' => $endpoint,
                        'method' => $method,
                        'originalError' => $error->getMessage(),
                    ]
                );
            }
            throw $error; // Re-throw our custom exceptions
        }
    }

    /**
     * Handle HTTP error responses and throw appropriate exception types
     *
     * @param Response $response
     * @throws ApiException|AuthenticationException|RateLimitException
     */
    private function handleErrorResponse(Response $response): void
    {
        $status = $response->getStatusCode();
        $errorMessage = "HTTP {$status}";
        $errorDetails = [
            'status' => $status,
            'url' => '',
        ];

        // Try to parse error details from response body
        $errorBody = $response->getBody();
        if (!empty($errorBody)) {
            try {
                $parsed = json_decode($errorBody, true);
                if (json_last_error() === JSON_ERROR_NONE && isset($parsed['message'])) {
                    $errorMessage = $parsed['message'];
                    $errorDetails = array_merge($errorDetails, $parsed);
                } else {
                    $errorDetails['responseBody'] = $errorBody;
                }
            } catch (\Exception $e) {
                $errorDetails['responseBody'] = $errorBody;
            }
        }

        // Throw appropriate exception type based on status code
        switch ($status) {
            case 401:
                throw new AuthenticationException(
                    strpos($errorMessage, 'HTTP') !== false ? 'Authentication failed' : $errorMessage,
                    $errorDetails
                );
            case 403:
                throw new AuthenticationException(
                    strpos($errorMessage, 'HTTP') !== false ? 'Access forbidden' : $errorMessage,
                    $errorDetails
                );
            case 404:
                throw new ApiException(
                    strpos($errorMessage, 'HTTP') !== false ? 'Resource not found' : $errorMessage,
                    $status,
                    $errorDetails
                );
            case 429:
                $retryAfter = $response->getHeader('Retry-After');
                throw new RateLimitException(
                    strpos($errorMessage, 'HTTP') !== false ? 'Rate limit exceeded' : $errorMessage,
                    $retryAfter ? (int)$retryAfter : null,
                    $errorDetails
                );
            case 500:
            case 502:
            case 503:
            case 504:
                throw new ApiException(
                    strpos($errorMessage, 'HTTP') !== false ? 'Server error' : $errorMessage,
                    $status,
                    $errorDetails
                );
            default:
                throw new ApiException($errorMessage, $status, $errorDetails);
        }
    }

    public function get(string $endpoint, array $headers = [])
    {
        return $this->request($endpoint, 'GET', $headers);
    }

    public function post(string $endpoint, $data = null, array $headers = [])
    {
        return $this->request($endpoint, 'POST', $headers, $data);
    }

    public function put(string $endpoint, $data = null, array $headers = [])
    {
        return $this->request($endpoint, 'PUT', $headers, $data);
    }

    public function delete(string $endpoint, array $headers = [])
    {
        return $this->request($endpoint, 'DELETE', $headers);
    }

    public function patch(string $endpoint, $data = null, array $headers = [])
    {
        return $this->request($endpoint, 'PATCH', $headers, $data);
    }
}

// Global API instance
$GLOBALS['aluvia_api'] = new ApiClient();
