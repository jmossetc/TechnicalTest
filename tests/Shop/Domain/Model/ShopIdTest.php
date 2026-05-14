<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Shop\Domain\Model;

use InvalidArgumentException;
use Mossetc\TechnicalTest\Shop\Domain\Model\ShopId;
use PHPUnit\Framework\TestCase;

final class ShopIdTest extends TestCase
{
    private const string VALID = '550e8400-e29b-41d4-a716-446655440000';

    public function testAcceptsValidUuid(): void
    {
        $id = new ShopId(self::VALID);
        $this->assertSame(self::VALID, $id->value);
    }

    public function testRejectsInvalidUuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ShopId('not-a-uuid');
    }

    public function testGenerateReturnsValidShopId(): void
    {
        $id   = ShopId::generate();
        $copy = new ShopId($id->value);
        $this->assertSame($id->value, $copy->value);
    }

    public function testEqualsTrueForSameValue(): void
    {
        $this->assertTrue((new ShopId(self::VALID))->equals(new ShopId(self::VALID)));
    }

    public function testEqualsFalseForDifferentValue(): void
    {
        $this->assertFalse((new ShopId(self::VALID))->equals(ShopId::generate()));
    }

    public function testToStringReturnsValue(): void
    {
        $this->assertSame(self::VALID, (string) new ShopId(self::VALID));
    }
}
