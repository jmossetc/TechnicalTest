<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Company\Domain\Repository;

use Mossetc\TechnicalTest\Company\Domain\Model\Company;
use Mossetc\TechnicalTest\Company\Domain\Model\CompanyId;
use Mossetc\TechnicalTest\Company\Domain\Model\CompanyName;
use Mossetc\TechnicalTest\Company\Domain\Model\CompanySearchCriteria;

interface CompanyRepositoryInterface
{
    public function save(Company $company): void;

    public function findById(CompanyId $id): ?Company;

    public function findByName(CompanyName $name): ?Company;

    /** @return list<Company> */
    public function findPaginatedByCriteria(CompanySearchCriteria $criteria, int $limit, int $offset): array;

    public function countByCriteria(CompanySearchCriteria $criteria): int;

    public function delete(CompanyId $id): void;
}
