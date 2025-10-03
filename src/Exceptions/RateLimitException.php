<?php

declare(strict_types=1);

namespace Aluvia\Exceptions;

/**
 * Thrown when rate limits are exceeded
 */
class RateLimitException extends AluviaException
{
    private ?int $retryAfter;

    public function getErrorCode(): string
    {
        return 'RATE_LIMIT_ERROR';
    }

    public function __construct(string $message = 'Rate limit exceeded', ?int $retryAfter = null, array $details = [], ?\Exception $previous = null)
    {
        $this->retryAfter = $retryAfter;
        parent::__construct($message, $details, 429, $previous);
    }

    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }
}
