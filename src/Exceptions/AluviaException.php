<?php

declare(strict_types=1);

namespace Aluvia\Exceptions;

use Exception;

/**
 * Base class for all Aluvia SDK exceptions
 */
abstract class AluviaException extends Exception
{
    /**
     * @var string Error code for this exception type
     */
    abstract public function getErrorCode(): string;

    /**
     * @var array<string, mixed> Additional error details
     */
    private array $details;

    /**
     * @param string $message Exception message
     * @param array<string, mixed> $details Additional error details
     * @param int $code Exception code (default: 0)
     * @param Exception|null $previous Previous exception for chaining
     */
    public function __construct(string $message = '', array $details = [], int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->details = $details;
    }

    /**
     * Get additional error details
     *
     * @return array<string, mixed>
     */
    public function getDetails(): array
    {
        return $this->details;
    }

    /**
     * Get a specific detail by key
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getDetail(string $key, $default = null)
    {
        return $this->details[$key] ?? $default;
    }

    /**
     * Get exception data as array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'code' => $this->getErrorCode(),
            'message' => $this->getMessage(),
            'details' => $this->getDetails(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
        ];
    }
}
