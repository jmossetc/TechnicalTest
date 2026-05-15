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
        self::assertSame($this->id, $shop->id);
        self::assertSame($this->companyId, $shop->companyId);
        self::assertSame($this->name, $shop->name);
        self::assertFalse($shop->isDigital);
        self::assertTrue($shop->isActive);
        self::assertNull($shop->email);
        self::assertNull($shop->phoneNumber);
        self::assertNull($shop->latitude);
        self::assertNull($shop->longitude);
    }

    public function testJsonSerializeContainsAllExpectedKeys(): void
    {
        $data = new Shop($this->id, $this->companyId, $this->name)->jsonSerialize();

        foreach (['id', 'company_id', 'name', 'email', 'phone_number',
            'address_line_1', 'address_line_2', 'city', 'postal_code', 'country',
            'latitude', 'longitude', 'is_digital', 'is_active',
            'created_at', 'updated_at'] as $key) {
            self::assertArrayHasKey($key, $data);
        }
    }

    public function testJsonSerializeValuesMatchProperties(): void
    {
        $shop = new Shop(
            id: $this->id,
            companyId: $this->companyId,
            name: $this->name,
            address: new ShopAddress(city: 'Paris', country: 'France'),
            email: 'shop@example.com',
            phoneNumber: '+33123456789',
            latitude: 48.8566,
            longitude: 2.3522,
            isDigital: true,
        );
        $data = $shop->jsonSerialize();

        self::assertSame($this->id->value, $data['id']);
        self::assertSame($this->companyId->value, $data['company_id']);
        self::assertSame('My Shop', $data['name']);
        self::assertSame('shop@example.com', $data['email']);
        self::assertSame('+33123456789', $data['phone_number']);
        self::assertSame('Paris', $data['city']);
        self::assertSame('France', $data['country']);
        self::assertSame(48.8566, $data['latitude']);
        self::assertSame(2.3522, $data['longitude']);
        self::assertTrue($data['is_digital']);
    }
}
