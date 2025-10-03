<?php

declare(strict_types=1);

namespace Aluvia\Exceptions;

/**
 * Thrown when a requested resource is not found
 */
class NotFoundException extends AluviaException
{
    public function getErrorCode(): string
    {
        return 'NOT_FOUND_ERROR';
    }

    public function __construct(string $message = 'Resource not found', array $details = [], int $code = 404, ?\Exception $previous = null)
    {
        parent::__construct($message, $details, $code, $previous);
    }
}
