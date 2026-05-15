<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Shop\Application;

use InvalidArgumentException;
use Mossetc\TechnicalTest\Shop\Application\Command\CreateShop;
use Mossetc\TechnicalTest\Shop\Application\Command\UpdateShop;
use Mossetc\TechnicalTest\Shop\Application\Handler\CreateShopHandler;
use Mossetc\TechnicalTest\Shop\Application\Handler\UpdateShopHandler;
use Mossetc\TechnicalTest\Shop\Domain\Exception\ShopAlreadyExistsException;
use Mossetc\TechnicalTest\Shop\Domain\Exception\ShopNotFoundException;
use Mossetc\TechnicalTest\Shop\Domain\Model\ShopId;
use Mossetc\TechnicalTest\Tests\Support\InMemoryShopRepository;
use PHPUnit\Framework\TestCase;

final class UpdateShopHandlerTest extends TestCase
{
    private const string COMPANY_A    = '11111111-1111-4111-8111-111111111111';
    private const string UNKNOWN_UUID = '550e8400-e29b-41d4-a716-446655440000';

    private InMemoryShopRepository $repository;
    private UpdateShopHandler      $handler;
    private ShopId                 $existingId;

    protected function setUp(): void
    {
        $this->repository = new InMemoryShopRepository();
        $this->handler    = new UpdateShopHandler($this->repository);
        $this->existingId = new CreateShopHandler($this->repository)->handle(
            new CreateShop(
                companyId: self::COMPANY_A,
                name: 'Original Name',
                email: 'old@example.com',
                city: 'Lyon',
                isDigital: false,
            ),
        );
    }

    public function testUpdatesName(): void
    {
        $this->handler->handle(new UpdateShop($this->existingId->value, 'New Name'));

        $shop = $this->repository->findById($this->existingId);
        self::assertNotNull($shop);
        self::assertSame('New Name', $shop->name->value);
    }

    public function testNullFieldsPreserveExistingValues(): void
    {
        $this->handler->handle(new UpdateShop(
            id: $this->existingId->value,
            name: 'New Name',
        ));

        $shop = $this->repository->findById($this->existingId);
        self::assertNotNull($shop);
        self::assertSame('old@example.com', $shop->email);
        self::assertSame('Lyon', $shop->address->city);
        self::assertFalse($shop->isDigital);
    }

    public function testUpdatesAllOptionalFields(): void
    {
        $this->handler->handle(new UpdateShop(
            id: $this->existingId->value,
            name: 'Updated Shop',
            email: 'new@example.com',
            phoneNumber: '+33987654321',
            city: 'Paris',
            postalCode: '75001',
            country: 'France',
            latitude: 48.8566,
            longitude: 2.3522,
            isDigital: true,
            isActive: false,
        ));

        $shop = $this->repository->findById($this->existingId);
        self::assertNotNull($shop);
        self::assertSame('new@example.com', $shop->email);
        self::assertSame('+33987654321', $shop->phoneNumber);
        self::assertSame('Paris', $shop->address->city);
        self::assertSame(48.8566, $shop->latitude);
        self::assertTrue($shop->isDigital);
        self::assertFalse($shop->isActive);
    }

    public function testRenamingToOwnCurrentNameDoesNotThrow(): void
    {
        $this->handler->handle(new UpdateShop($this->existingId->value, 'Original Name'));

        $shop = $this->repository->findById($this->existingId);
        self::assertNotNull($shop);
        self::assertSame('Original Name', $shop->name->value);
    }

    public function testThrowsWhenNewNameConflictsWithDifferentShop(): void
    {
        new CreateShopHandler($this->repository)
            ->handle(new CreateShop(self::COMPANY_A, 'Taken Name'));

        $this->expectException(ShopAlreadyExistsException::class);
        $this->handler->handle(new UpdateShop($this->existingId->value, 'Taken Name'));
    }

    public function testThrowsWhenShopNotFound(): void
    {
        $this->expectException(ShopNotFoundException::class);
        $this->handler->handle(new UpdateShop(self::UNKNOWN_UUID, 'Any Name'));
    }

    public function testThrowsForInvalidShopId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->handler->handle(new UpdateShop('not-a-uuid', 'Any Name'));
    }
}
