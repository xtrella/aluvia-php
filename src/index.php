<?php

declare(strict_types=1);

/**
 * Aluvia PHP SDK - Official proxy management SDK for PHP
 *
 * This file provides the main entry point for the Aluvia PHP SDK.
 *
 * @package Aluvia
 * @version 1.0.0
 * @author Aluvia Team <support@aluvia.io>
 * @license MIT
 */

// Autoload all required classes
require_once __DIR__ . '/Exceptions/AluviaException.php';
require_once __DIR__ . '/Exceptions/AuthenticationException.php';
require_once __DIR__ . '/Exceptions/NetworkException.php';
require_once __DIR__ . '/Exceptions/ApiException.php';
require_once __DIR__ . '/Exceptions/ValidationException.php';
require_once __DIR__ . '/Exceptions/NotFoundException.php';
require_once __DIR__ . '/Exceptions/RateLimitException.php';
require_once __DIR__ . '/Validator.php';
require_once __DIR__ . '/Http/HttpClient.php';
require_once __DIR__ . '/ApiClient.php';
require_once __DIR__ . '/Proxy.php';
require_once __DIR__ . '/Aluvia.php';

// Export main classes for easy access
use Aluvia\Aluvia;
use Aluvia\Proxy;
use Aluvia\ProxyCredential;
use Aluvia\ProxyConfig;
use Aluvia\Validator;

// Export exceptions
use Aluvia\Exceptions\AluviaException;
use Aluvia\Exceptions\AuthenticationException;
use Aluvia\Exceptions\NetworkException;
use Aluvia\Exceptions\ApiException;
use Aluvia\Exceptions\ValidationException;
use Aluvia\Exceptions\NotFoundException;
use Aluvia\Exceptions\RateLimitException;

/**
 * Create a new Aluvia SDK instance
 *
 * @param string $token Your Aluvia API authentication token
 * @return Aluvia The Aluvia SDK instance
 */
function createAluvia(string $token): Aluvia
{
    return new Aluvia($token);
}

// Make the main class available globally for convenience
if (!class_exists('AluviaSDK')) {
    class_alias('Aluvia\\Aluvia', 'AluviaSDK');
}
