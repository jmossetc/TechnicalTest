<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Unit\Auth\Domain;

use InvalidArgumentException;
use Mossetc\TechnicalTest\Auth\Domain\Model\PlainPassword;
use PHPUnit\Framework\TestCase;

final class PlainPasswordTest extends TestCase
{
    public function testAcceptsPasswordOfMinimumLength(): void
    {
        $password = new PlainPassword('12345678');

        $this->assertSame('12345678', $password->value);
    }

    public function testAcceptsLongPassword(): void
    {
        $long = str_repeat('a', 100);
        $password = new PlainPassword($long);

        $this->assertSame($long, $password->value);
    }

    public function testRejectsTooShortPassword(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PlainPassword('short');
    }

    public function testRejectsEmptyPassword(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PlainPassword('');
    }

    public function testRejectsSevenCharacterPassword(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PlainPassword('1234567');
    }
}
