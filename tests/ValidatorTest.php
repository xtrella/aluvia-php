<?php

declare(strict_types=1);

namespace Aluvia\Tests;

use PHPUnit\Framework\TestCase;
use Aluvia\Validator;
use Aluvia\Exceptions\ValidationException;

/**
 * @covers \Aluvia\Validator
 */
class ValidatorTest extends TestCase
{
    public function testValidateRequiredStringWithValidInput(): void
    {
        $result = Validator::validateRequiredString('test string', 'test field');
        $this->assertEquals('test string', $result);
    }

    public function testValidateRequiredStringTrimsWhitespace(): void
    {
        $result = Validator::validateRequiredString('  test string  ', 'test field');
        $this->assertEquals('test string', $result);
    }

    public function testValidateRequiredStringThrowsExceptionForNonString(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('test field must be a string');

        Validator::validateRequiredString(123, 'test field');
    }

    public function testValidateRequiredStringThrowsExceptionForEmptyString(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('test field cannot be empty');

        Validator::validateRequiredString('', 'test field');
    }

    public function testValidateRequiredStringThrowsExceptionForWhitespaceOnly(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('test field cannot be empty');

        Validator::validateRequiredString('   ', 'test field');
    }

    public function testValidatePositiveNumberWithValidInput(): void
    {
        $result = Validator::validatePositiveNumber(5, 'test field');
        $this->assertEquals(5, $result);
    }

    public function testValidatePositiveNumberWithCustomMinimum(): void
    {
        $result = Validator::validatePositiveNumber(10, 'test field', 5);
        $this->assertEquals(10, $result);
    }

    public function testValidatePositiveNumberWithMaximum(): void
    {
        $result = Validator::validatePositiveNumber(5, 'test field', 1, 10);
        $this->assertEquals(5, $result);
    }

    public function testValidatePositiveNumberThrowsExceptionForNonInteger(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('test field must be a positive integer');

        Validator::validatePositiveNumber('not a number', 'test field');
    }

    public function testValidatePositiveNumberThrowsExceptionForFloat(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('test field must be a positive integer');

        Validator::validatePositiveNumber(5.5, 'test field');
    }

    public function testValidatePositiveNumberThrowsExceptionForTooSmall(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('test field must be at least 5');

        Validator::validatePositiveNumber(3, 'test field', 5);
    }

    public function testValidatePositiveNumberThrowsExceptionForTooLarge(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('test field cannot exceed 10');

        Validator::validatePositiveNumber(15, 'test field', 1, 10);
    }

    public function testValidateApiTokenWithValidToken(): void
    {
        $result = Validator::validateApiToken('alv_1234567890abcdef');
        $this->assertEquals('alv_1234567890abcdef', $result);
    }

    public function testValidateApiTokenThrowsExceptionForShortToken(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('API token appears to be too short');

        Validator::validateApiToken('short');
    }

    public function testValidateUsernameWithValidUsername(): void
    {
        $result = Validator::validateUsername('testuser123');
        $this->assertEquals('testuser123', $result);
    }

    public function testValidateUsernameThrowsExceptionForTooLong(): void
    {
        $longUsername = str_repeat('a', 101);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Username is too long');

        Validator::validateUsername($longUsername);
    }

    public function testValidateProxyCountWithValidCount(): void
    {
        $result = Validator::validateProxyCount(5);
        $this->assertEquals(5, $result);
    }

    public function testValidateProxyCountThrowsExceptionForTooLarge(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('proxy count cannot exceed 100');

        Validator::validateProxyCount(150);
    }

    public function testValidateProxyCountThrowsExceptionForZero(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('proxy count must be at least 1');

        Validator::validateProxyCount(0);
    }
}
