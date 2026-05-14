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
        $this->assertNull($addr->addressLine1);
        $this->assertNull($addr->addressLine2);
        $this->assertNull($addr->city);
        $this->assertNull($addr->postalCode);
        $this->assertNull($addr->country);
    }

    public function testStoresAllFields(): void
    {
        $addr = new ShopAddress(
            addressLine1: '10 Rue de la Paix',
            addressLine2: 'Bâtiment B',
            city:         'Paris',
            postalCode:   '75001',
            country:      'France',
        );
        $this->assertSame('10 Rue de la Paix', $addr->addressLine1);
        $this->assertSame('Bâtiment B',        $addr->addressLine2);
        $this->assertSame('Paris',             $addr->city);
        $this->assertSame('75001',             $addr->postalCode);
        $this->assertSame('France',            $addr->country);
    }
}
