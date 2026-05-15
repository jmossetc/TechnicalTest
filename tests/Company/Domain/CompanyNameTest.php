<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Company\Domain;

use InvalidArgumentException;
use Mossetc\TechnicalTest\Company\Domain\Model\CompanyName;
use PHPUnit\Framework\TestCase;

final class CompanyNameTest extends TestCase
{
    public function testAcceptsValidName(): void
    {
        $name = new CompanyName('Acme Corp');

        self::assertSame('Acme Corp', $name->value);
    }

    public function testRejectsEmptyString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new CompanyName('');
    }

    public function testRejectsWhitespaceOnly(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new CompanyName('   ');
    }

    public function testRejectsNameExceeding255Characters(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new CompanyName(str_repeat('a', 256));
    }

    public function testAcceptsNameOfExactly255Characters(): void
    {
        $name = new CompanyName(str_repeat('a', 255));

        self::assertSame(255, \strlen($name->value));
    }

    public function testEqualityForSameName(): void
    {
        self::assertTrue(new CompanyName('Acme')->equals(new CompanyName('Acme')));
    }

    public function testInequalityForDifferentNames(): void
    {
        self::assertFalse(new CompanyName('Acme')->equals(new CompanyName('Globex')));
    }

    public function testEqualityIsCaseSensitive(): void
    {
        self::assertFalse(new CompanyName('acme')->equals(new CompanyName('ACME')));
    }

    public function testToString(): void
    {
        self::assertSame('Acme Corp', (string) new CompanyName('Acme Corp'));
    }
}
