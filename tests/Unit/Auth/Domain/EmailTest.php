<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Unit\Auth\Domain;

use InvalidArgumentException;
use Mossetc\TechnicalTest\Auth\Domain\Model\Email;
use PHPUnit\Framework\TestCase;

final class EmailTest extends TestCase
{
    public function testNormalizesToLowercase(): void
    {
        $email = new Email('User@Example.COM');

        $this->assertSame('user@example.com', $email->value);
    }

    public function testTrimsWhitespace(): void
    {
        $email = new Email('  user@example.com  ');

        $this->assertSame('user@example.com', $email->value);
    }

    public function testRejectsInvalidEmail(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Email('not-an-email');
    }

    public function testRejectsEmptyEmail(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Email('');
    }

    public function testRejectsMissingAtSign(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Email('useratexample.com');
    }

    public function testEqualityIsCaseSensitiveAfterNormalization(): void
    {
        $a = new Email('user@example.com');
        $b = new Email('USER@EXAMPLE.COM');

        $this->assertTrue($a->equals($b));
    }

    public function testDifferentEmailsAreNotEqual(): void
    {
        $a = new Email('alice@example.com');
        $b = new Email('bob@example.com');

        $this->assertFalse($a->equals($b));
    }

    public function testToString(): void
    {
        $email = new Email('user@example.com');

        $this->assertSame('user@example.com', (string) $email);
    }
}
