<?php

declare(strict_types=1);

namespace Aluvia\Exceptions;

/**
 * Thrown when API authentication fails
 */
class AuthenticationException extends AluviaException
{
    public function getErrorCode(): string
    {
        return 'AUTHENTICATION_ERROR';
    }

    public function __construct(string $message = 'Authentication failed', array $details = [], int $code = 401, ?\Exception $previous = null)
    {
        parent::__construct($message, $details, $code, $previous);
    }
}
