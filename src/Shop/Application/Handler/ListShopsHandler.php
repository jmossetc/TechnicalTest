<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Shop\Application\Handler;

use Mossetc\TechnicalTest\Shop\Application\Command\ListShops;
use Mossetc\TechnicalTest\Shop\Application\DTO\PaginatedShops;
use Mossetc\TechnicalTest\Shop\Domain\Repository\ShopRepositoryInterface;

final readonly class ListShopsHandler
{
    public function __construct(private ShopRepositoryInterface $repository) {}

    public function handle(ListShops $query): PaginatedShops
    {
        $offset = ($query->page - 1) * $query->limit;

        return new PaginatedShops(
            shops: $this->repository->findPaginatedByCriteria(
                $query->criteria,
                $query->sort,
                $query->limit,
                $offset,
            ),
            total: $this->repository->countByCriteria($query->criteria),
            page: $query->page,
            limit: $query->limit,
        );
    }
}
