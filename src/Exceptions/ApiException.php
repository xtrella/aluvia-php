<?php

declare(strict_types=1);

namespace Aluvia\Exceptions;

/**
 * Thrown when API returns an error response
 */
class ApiException extends AluviaException
{
    private ?int $statusCode;

    public function getErrorCode(): string
    {
        return 'API_ERROR';
    }

    public function __construct(string $message = 'API request failed', ?int $statusCode = null, array $details = [], ?\Exception $previous = null)
    {
        $this->statusCode = $statusCode;
        $code = $statusCode ?? 0;
        parent::__construct($message, $details, $code, $previous);
    }

    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }
}
