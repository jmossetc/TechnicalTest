<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Shop\Application;

use InvalidArgumentException;
use Mossetc\TechnicalTest\Shop\Application\Command\CreateShop;
use Mossetc\TechnicalTest\Shop\Application\Handler\CreateShopHandler;
use Mossetc\TechnicalTest\Shop\Domain\Exception\ShopAlreadyExistsException;
use Mossetc\TechnicalTest\Shop\Domain\Model\ShopId;
use Mossetc\TechnicalTest\Tests\Support\InMemoryShopRepository;
use PHPUnit\Framework\TestCase;

final class CreateShopHandlerTest extends TestCase
{
    private const string COMPANY_A = '11111111-1111-4111-8111-111111111111';
    private const string COMPANY_B = '22222222-2222-4222-8222-222222222222';

    private InMemoryShopRepository $repository;
    private CreateShopHandler      $handler;

    protected function setUp(): void
    {
        $this->repository = new InMemoryShopRepository();
        $this->handler    = new CreateShopHandler($this->repository);
    }

    public function testCreatesShopAndReturnsId(): void
    {
        $id = $this->handler->handle(new CreateShop(self::COMPANY_A, 'My Shop'));
        self::assertNotNull($this->repository->findById($id));
    }

    public function testReturnedIdMatchesSavedShop(): void
    {
        $id   = $this->handler->handle(new CreateShop(self::COMPANY_A, 'My Shop'));
        $shop = $this->repository->findById($id);
        self::assertNotNull($shop);
        self::assertTrue($id->equals($shop->id));
    }

    public function testStoresAllOptionalFields(): void
    {
        $id = $this->handler->handle(new CreateShop(
            companyId: self::COMPANY_A,
            name: 'Full Shop',
            email: 'shop@example.com',
            phoneNumber: '+33123456789',
            addressLine1: '10 Rue de la Paix',
            addressLine2: 'Bâtiment B',
            city: 'Paris',
            postalCode: '75001',
            country: 'France',
            latitude: 48.8566,
            longitude: 2.3522,
            isDigital: true,
        ));

        $shop = $this->repository->findById($id);
        self::assertNotNull($shop);
        self::assertSame('shop@example.com', $shop->email);
        self::assertSame('+33123456789', $shop->phoneNumber);
        self::assertSame('10 Rue de la Paix', $shop->address->addressLine1);
        self::assertSame('Bâtiment B', $shop->address->addressLine2);
        self::assertSame('Paris', $shop->address->city);
        self::assertSame('75001', $shop->address->postalCode);
        self::assertSame('France', $shop->address->country);
        self::assertSame(48.8566, $shop->latitude);
        self::assertSame(2.3522, $shop->longitude);
        self::assertTrue($shop->isDigital);
    }

    public function testThrowsWhenNameAlreadyExistsInSameCompany(): void
    {
        $this->handler->handle(new CreateShop(self::COMPANY_A, 'Taken'));
        $this->expectException(ShopAlreadyExistsException::class);
        $this->handler->handle(new CreateShop(self::COMPANY_A, 'Taken'));
    }

    public function testSameNameInDifferentCompanyIsAllowed(): void
    {
        $this->handler->handle(new CreateShop(self::COMPANY_A, 'My Shop'));
        $id = $this->handler->handle(new CreateShop(self::COMPANY_B, 'My Shop'));
        self::assertNotNull($this->repository->findById($id));
    }

    public function testThrowsForInvalidCompanyId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->handler->handle(new CreateShop('not-a-uuid', 'My Shop'));
    }

    public function testThrowsForEmptyName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->handler->handle(new CreateShop(self::COMPANY_A, ''));
    }
}
