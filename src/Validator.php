<?php

declare(strict_types=1);

namespace Aluvia;

use Aluvia\Exceptions\ValidationException;

/**
 * Input validation utilities for the Aluvia SDK
 */
class Validator
{
    /**
     * Validates that a string is not empty or whitespace-only
     *
     * @param mixed $value The value to validate
     * @param string $fieldName The name of the field being validated
     * @return string The trimmed valid string
     * @throws ValidationException
     */
    public static function validateRequiredString($value, string $fieldName): string
    {
        if (!is_string($value)) {
            throw new ValidationException(
                "{$fieldName} must be a string",
                [
                    'field' => $fieldName,
                    'received' => gettype($value),
                ]
            );
        }

        $trimmed = trim($value);
        if (empty($trimmed)) {
            throw new ValidationException(
                "{$fieldName} cannot be empty",
                [
                    'field' => $fieldName,
                    'value' => $value,
                ]
            );
        }

        return $trimmed;
    }

    /**
     * Validates that a number is positive and within optional bounds
     *
     * @param mixed $value The value to validate
     * @param string $fieldName The name of the field being validated
     * @param int $min Minimum allowed value (default: 1)
     * @param int|null $max Maximum allowed value (optional)
     * @return int The valid number
     * @throws ValidationException
     */
    public static function validatePositiveNumber($value, string $fieldName, int $min = 1, ?int $max = null): int
    {
        if (!is_numeric($value) || !is_int((int)$value) || (int)$value != $value) {
            throw new ValidationException(
                "{$fieldName} must be a positive integer",
                [
                    'field' => $fieldName,
                    'received' => gettype($value),
                    'value' => $value,
                ]
            );
        }

        $intValue = (int)$value;

        if ($intValue < $min) {
            throw new ValidationException(
                "{$fieldName} must be at least {$min}",
                [
                    'field' => $fieldName,
                    'value' => $intValue,
                    'minimum' => $min,
                ]
            );
        }

        if ($max !== null && $intValue > $max) {
            throw new ValidationException(
                "{$fieldName} cannot exceed {$max}",
                [
                    'field' => $fieldName,
                    'value' => $intValue,
                    'maximum' => $max,
                ]
            );
        }

        return $intValue;
    }

    /**
     * Validates API token format
     *
     * @param mixed $token The token to validate
     * @return string The valid token
     * @throws ValidationException
     */
    public static function validateApiToken($token): string
    {
        $validToken = self::validateRequiredString($token, 'API token');

        if (strlen($validToken) < 10) {
            throw new ValidationException(
                'API token appears to be too short',
                [
                    'field' => 'token',
                    'minLength' => 10,
                    'actualLength' => strlen($validToken),
                ]
            );
        }

        return $validToken;
    }

    /**
     * Validates username format
     *
     * @param mixed $username The username to validate
     * @return string The valid username
     * @throws ValidationException
     */
    public static function validateUsername($username): string
    {
        $validUsername = self::validateRequiredString($username, 'username');

        // Check for reasonable username constraints
        if (strlen($validUsername) > 100) {
            throw new ValidationException(
                'Username is too long',
                [
                    'field' => 'username',
                    'maxLength' => 100,
                    'actualLength' => strlen($validUsername),
                ]
            );
        }

        return $validUsername;
    }

    /**
     * Validates proxy count for creation
     *
     * @param mixed $count The count to validate
     * @return int The valid count
     * @throws ValidationException
     */
    public static function validateProxyCount($count): int
    {
        return self::validatePositiveNumber($count, 'proxy count', 1, 100);
    }
}
