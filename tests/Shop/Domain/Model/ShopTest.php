<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Shop\Domain\Model;

use Mossetc\TechnicalTest\Company\Domain\Model\CompanyId;
use Mossetc\TechnicalTest\Shop\Domain\Model\Shop;
use Mossetc\TechnicalTest\Shop\Domain\Model\ShopAddress;
use Mossetc\TechnicalTest\Shop\Domain\Model\ShopId;
use Mossetc\TechnicalTest\Shop\Domain\Model\ShopName;
use PHPUnit\Framework\TestCase;

final class ShopTest extends TestCase
{
    private ShopId    $id;
    private CompanyId $companyId;
    private ShopName  $name;

    protected function setUp(): void
    {
        $this->id        = ShopId::generate();
        $this->companyId = new CompanyId('11111111-1111-4111-8111-111111111111');
        $this->name      = new ShopName('My Shop');
    }

    public function testConstructsWithDefaults(): void
    {
        $shop = new Shop($this->id, $this->companyId, $this->name);
        $this->assertSame($this->id,        $shop->id);
        $this->assertSame($this->companyId, $shop->companyId);
        $this->assertSame($this->name,      $shop->name);
        $this->assertFalse($shop->isDigital);
        $this->assertTrue($shop->isActive);
        $this->assertNull($shop->email);
        $this->assertNull($shop->phoneNumber);
        $this->assertNull($shop->latitude);
        $this->assertNull($shop->longitude);
    }

    public function testJsonSerializeContainsAllExpectedKeys(): void
    {
        $data = (new Shop($this->id, $this->companyId, $this->name))->jsonSerialize();

        foreach (['id', 'company_id', 'name', 'email', 'phone_number',
                  'address_line_1', 'address_line_2', 'city', 'postal_code', 'country',
                  'latitude', 'longitude', 'is_digital', 'is_active',
                  'created_at', 'updated_at'] as $key) {
            $this->assertArrayHasKey($key, $data);
        }
    }

    public function testJsonSerializeValuesMatchProperties(): void
    {
        $shop = new Shop(
            id:          $this->id,
            companyId:   $this->companyId,
            name:        $this->name,
            address:     new ShopAddress(city: 'Paris', country: 'France'),
            email:       'shop@example.com',
            phoneNumber: '+33123456789',
            latitude:    48.8566,
            longitude:   2.3522,
            isDigital:   true,
        );
        $data = $shop->jsonSerialize();

        $this->assertSame($this->id->value,        $data['id']);
        $this->assertSame($this->companyId->value, $data['company_id']);
        $this->assertSame('My Shop',               $data['name']);
        $this->assertSame('shop@example.com',      $data['email']);
        $this->assertSame('+33123456789',           $data['phone_number']);
        $this->assertSame('Paris',                 $data['city']);
        $this->assertSame('France',                $data['country']);
        $this->assertSame(48.8566,                 $data['latitude']);
        $this->assertSame(2.3522,                  $data['longitude']);
        $this->assertTrue($data['is_digital']);
    }
}
