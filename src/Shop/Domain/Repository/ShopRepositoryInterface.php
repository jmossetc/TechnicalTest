<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Shop\Domain\Repository;

use Mossetc\TechnicalTest\Company\Domain\Model\CompanyId;
use Mossetc\TechnicalTest\Shop\Domain\Model\Shop;
use Mossetc\TechnicalTest\Shop\Domain\Model\ShopId;
use Mossetc\TechnicalTest\Shop\Domain\Model\ShopName;
use Mossetc\TechnicalTest\Shop\Domain\Model\ShopSearchCriteria;
use Mossetc\TechnicalTest\Shop\Domain\Model\ShopSortCriteria;

interface ShopRepositoryInterface
{
    public function save(Shop $shop): void;

    public function findById(ShopId $id): ?Shop;

    public function findByNameAndCompany(ShopName $name, CompanyId $companyId): ?Shop;

    /** @return list<Shop> */
    public function findPaginatedByCriteria(
        ShopSearchCriteria $criteria,
        ShopSortCriteria   $sort,
        int                $limit,
        int                $offset,
    ): array;

    public function countByCriteria(ShopSearchCriteria $criteria): int;

    public function delete(ShopId $id): void;
}
