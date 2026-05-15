<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Auth\Domain\Model;

use InvalidArgumentException;
use Mossetc\TechnicalTest\Auth\Domain\Model\FirstName;
use PHPUnit\Framework\TestCase;

final class FirstNameTest extends TestCase
{
    public function testAcceptsValidName(): void
    {
        $name = new FirstName('Alice');

        self::assertSame('Alice', $name->value);
    }

    public function testRejectsEmptyString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new FirstName('');
    }

    public function testRejectsWhitespaceOnly(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new FirstName('   ');
    }

    public function testRejectsNameExceeding255Characters(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new FirstName(str_repeat('a', 256));
    }

    public function testAcceptsNameOfExactly255Characters(): void
    {
        $name = new FirstName(str_repeat('a', 255));

        self::assertSame(255, \strlen($name->value));
    }

    public function testPreservesOriginalCasing(): void
    {
        $name = new FirstName('Jean-Pierre');

        self::assertSame('Jean-Pierre', $name->value);
    }
}
