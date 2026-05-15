<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Auth\Domain;

use Mossetc\TechnicalTest\Auth\Domain\Exception\ForbiddenException;
use Mossetc\TechnicalTest\Auth\Domain\Exception\InvalidCredentialsException;
use Mossetc\TechnicalTest\Auth\Domain\Exception\InvalidTokenException;
use Mossetc\TechnicalTest\Auth\Domain\Exception\UserAlreadyExistsException;
use Mossetc\TechnicalTest\Auth\Domain\Exception\UserNotFoundException;
use Mossetc\TechnicalTest\Auth\Domain\Model\Email;
use PHPUnit\Framework\TestCase;

final class ExceptionsTest extends TestCase
{
    public function testForbiddenExceptionHasDefaultMessage(): void
    {
        $e = new ForbiddenException();
        self::assertNotEmpty($e->getMessage());
    }

    public function testForbiddenExceptionAcceptsCustomMessage(): void
    {
        $e = new ForbiddenException('Custom message');
        self::assertSame('Custom message', $e->getMessage());
    }

    public function testInvalidCredentialsExceptionHasMessage(): void
    {
        $e = new InvalidCredentialsException();
        self::assertNotEmpty($e->getMessage());
    }

    public function testInvalidTokenExceptionIncludesReason(): void
    {
        $e = new InvalidTokenException('token expired');
        self::assertStringContainsString('token expired', $e->getMessage());
    }

    public function testUserAlreadyExistsExceptionIncludesEmail(): void
    {
        $e = new UserAlreadyExistsException(new Email('alice@example.com'));
        self::assertStringContainsString('alice@example.com', $e->getMessage());
    }

    public function testUserNotFoundExceptionHasMessage(): void
    {
        $e = new UserNotFoundException();
        self::assertNotEmpty($e->getMessage());
    }
}
