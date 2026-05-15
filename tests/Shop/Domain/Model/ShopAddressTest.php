<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Shop\Domain\Model;

use Mossetc\TechnicalTest\Shop\Domain\Model\ShopAddress;
use PHPUnit\Framework\TestCase;

final class ShopAddressTest extends TestCase
{
    public function testDefaultsAllToNull(): void
    {
        $addr = new ShopAddress();
        self::assertNull($addr->addressLine1);
        self::assertNull($addr->addressLine2);
        self::assertNull($addr->city);
        self::assertNull($addr->postalCode);
        self::assertNull($addr->country);
    }

    public function testStoresAllFields(): void
    {
        $addr = new ShopAddress(
            addressLine1: '10 Rue de la Paix',
            addressLine2: 'Bâtiment B',
            city: 'Paris',
            postalCode: '75001',
            country: 'France',
        );
        self::assertSame('10 Rue de la Paix', $addr->addressLine1);
        self::assertSame('Bâtiment B', $addr->addressLine2);
        self::assertSame('Paris', $addr->city);
        self::assertSame('75001', $addr->postalCode);
        self::assertSame('France', $addr->country);
    }
}
