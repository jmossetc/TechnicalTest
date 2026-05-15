<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Auth\Domain\Model;

use Mossetc\TechnicalTest\Auth\Domain\Model\AuthToken;
use PHPUnit\Framework\TestCase;

class AuthTokenTest extends TestCase
{
    public function testRejectsEmptyToken(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Auth token cannot be empty');

        new AuthToken('');
    }

    public function testToString(): void
    {
        $token = new AuthToken('token-value');
        self::assertSame('token-value', (string) $token);
    }
}
