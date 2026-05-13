<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Auth\Domain\Model;

use InvalidArgumentException;
use Mossetc\TechnicalTest\Auth\Domain\Model\LastName;
use PHPUnit\Framework\TestCase;

final class LastNameTest extends TestCase
{
    public function testAcceptsValidName(): void
    {
        $name = new LastName('Smith');

        $this->assertSame('Smith', $name->value);
    }

    public function testRejectsEmptyString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new LastName('');
    }

    public function testRejectsWhitespaceOnly(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new LastName('   ');
    }

    public function testRejectsNameExceeding255Characters(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new LastName(str_repeat('b', 256));
    }

    public function testAcceptsNameOfExactly255Characters(): void
    {
        $name = new LastName(str_repeat('b', 255));

        $this->assertSame(255, strlen($name->value));
    }
}
