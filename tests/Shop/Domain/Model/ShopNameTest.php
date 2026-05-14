<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Shop\Domain\Model;

use InvalidArgumentException;
use Mossetc\TechnicalTest\Shop\Domain\Model\ShopName;
use PHPUnit\Framework\TestCase;

final class ShopNameTest extends TestCase
{
    public function testAcceptsValidName(): void
    {
        $name = new ShopName('My Shop');
        $this->assertSame('My Shop', $name->value);
    }

    public function testRejectsEmptyString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ShopName('');
    }

    public function testRejectsWhitespaceOnly(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ShopName('   ');
    }

    public function testRejectsNameExceeding255Characters(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ShopName(str_repeat('a', 256));
    }

    public function testAcceptsNameOf255Characters(): void
    {
        $name = new ShopName(str_repeat('a', 255));
        $this->assertSame(255, strlen($name->value));
    }

    public function testEqualsTrueForSameValue(): void
    {
        $this->assertTrue((new ShopName('Foo'))->equals(new ShopName('Foo')));
    }

    public function testEqualsFalseForDifferentValue(): void
    {
        $this->assertFalse((new ShopName('Foo'))->equals(new ShopName('Bar')));
    }

    public function testToStringReturnsValue(): void
    {
        $this->assertSame('My Shop', (string) new ShopName('My Shop'));
    }
}
