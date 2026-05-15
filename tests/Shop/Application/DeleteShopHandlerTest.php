<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Shop\Application;

use InvalidArgumentException;
use Mossetc\TechnicalTest\Shop\Application\Command\CreateShop;
use Mossetc\TechnicalTest\Shop\Application\Handler\CreateShopHandler;
use Mossetc\TechnicalTest\Shop\Application\Handler\DeleteShopHandler;
use Mossetc\TechnicalTest\Shop\Domain\Exception\ShopNotFoundException;
use Mossetc\TechnicalTest\Shop\Domain\Model\ShopId;
use Mossetc\TechnicalTest\Tests\Support\InMemoryShopRepository;
use PHPUnit\Framework\TestCase;

final class DeleteShopHandlerTest extends TestCase
{
    private const string COMPANY_A    = '11111111-1111-4111-8111-111111111111';
    private const string UNKNOWN_UUID = '550e8400-e29b-41d4-a716-446655440000';

    private InMemoryShopRepository $repository;
    private DeleteShopHandler      $handler;
    private ShopId                 $existingId;

    protected function setUp(): void
    {
        $this->repository = new InMemoryShopRepository();
        $this->handler    = new DeleteShopHandler($this->repository);
        $this->existingId = new CreateShopHandler($this->repository)
            ->handle(new CreateShop(self::COMPANY_A, 'My Shop'));
    }

    public function testDeletesShop(): void
    {
        $this->handler->handle($this->existingId->value);
        self::assertNull($this->repository->findById($this->existingId));
    }

    public function testThrowsShopNotFoundExceptionForUnknownId(): void
    {
        $this->expectException(ShopNotFoundException::class);
        $this->handler->handle(self::UNKNOWN_UUID);
    }

    public function testThrowsInvalidArgumentExceptionForMalformedId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->handler->handle('not-a-uuid');
    }
}
