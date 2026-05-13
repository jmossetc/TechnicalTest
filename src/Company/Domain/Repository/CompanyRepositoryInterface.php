<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Company\Domain\Repository;

use Mossetc\TechnicalTest\Company\Domain\Model\Company;
use Mossetc\TechnicalTest\Company\Domain\Model\CompanyId;
use Mossetc\TechnicalTest\Company\Domain\Model\CompanyName;

interface CompanyRepositoryInterface
{
    public function save(Company $company): void;

    public function findById(CompanyId $id): ?Company;

    public function findByName(CompanyName $name): ?Company;

    /**
     * @return list<Company>
     */
    public function findPaginated(int $limit, int $offset): array;

    public function count(): int;

    public function delete(CompanyId $id): void;
}
