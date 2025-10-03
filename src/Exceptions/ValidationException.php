<?php

declare(strict_types=1);

namespace Aluvia\Exceptions;

/**
 * Thrown when input validation fails
 */
class ValidationException extends AluviaException
{
    public function getErrorCode(): string
    {
        return 'VALIDATION_ERROR';
    }

    public function __construct(string $message = 'Validation failed', array $details = [], int $code = 0, ?\Exception $previous = null)
    {
        parent::__construct($message, $details, $code, $previous);
    }
}
