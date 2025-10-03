<?php

declare(strict_types=1);

namespace Aluvia\Exceptions;

/**
 * Thrown when API requests fail due to network issues
 */
class NetworkException extends AluviaException
{
    public function getErrorCode(): string
    {
        return 'NETWORK_ERROR';
    }

    public function __construct(string $message = 'Network request failed', array $details = [], int $code = 0, ?\Exception $previous = null)
    {
        parent::__construct($message, $details, $code, $previous);
    }
}
