<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Shop\Application\Handler;

use InvalidArgumentException;
use Mossetc\TechnicalTest\Company\Domain\Model\CompanyId;
use Mossetc\TechnicalTest\Shop\Application\Command\ListShops;
use Mossetc\TechnicalTest\Shop\Application\DTO\PaginatedShops;
use Mossetc\TechnicalTest\Shop\Domain\Repository\ShopRepositoryInterface;

final readonly class ListShopsHandler
{
    public function __construct(private ShopRepositoryInterface $repository) {}

    public function handle(ListShops $query): PaginatedShops
    {
        try {
            $companyId = new CompanyId($query->companyId);
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException("Invalid company ID: {$query->companyId}", previous: $e);
        }

        $offset = ($query->page - 1) * $query->limit;

        return new PaginatedShops(
            shops: $this->repository->findPaginatedByCompany($companyId, $query->limit, $offset),
            total: $this->repository->countByCompany($companyId),
            page:  $query->page,
            limit: $query->limit,
        );
    }
}
