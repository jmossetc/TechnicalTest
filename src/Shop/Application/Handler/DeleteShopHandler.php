<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Shop\Application\Handler;

use InvalidArgumentException;
use Mossetc\TechnicalTest\Shop\Domain\Exception\ShopNotFoundException;
use Mossetc\TechnicalTest\Shop\Domain\Model\ShopId;
use Mossetc\TechnicalTest\Shop\Domain\Repository\ShopRepositoryInterface;

final readonly class DeleteShopHandler
{
    public function __construct(private ShopRepositoryInterface $repository) {}

    public function handle(string $shopId): void
    {
        try {
            $id = new ShopId($shopId);
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException("Invalid shop ID: {$shopId}", previous: $e);
        }

        if ($this->repository->findById($id) === null) {
            throw new ShopNotFoundException($shopId);
        }

        $this->repository->delete($id);
    }
}
