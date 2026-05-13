<?php

declare(strict_types=1);

use Mossetc\TechnicalTest\Company\Domain\Model\Company;
use Mossetc\TechnicalTest\Company\Domain\Model\CompanyId;
use Mossetc\TechnicalTest\Company\Domain\Model\CompanyName;
use Mossetc\TechnicalTest\Company\Domain\Repository\CompanyRepositoryInterface;

/**
 * In-memory implementation of CompanyRepositoryInterface for Behat scenarios.
 * Lives in features/bootstrap/ so it is never shipped to production.
 */
final class InMemoryCompanyRepository implements CompanyRepositoryInterface
{
    /** @var array<string, Company> */
    private array $store = [];

    public function save(Company $company): void
    {
        $this->store[$company->id->value] = $company;
    }

    public function findById(CompanyId $id): ?Company
    {
        return $this->store[$id->value] ?? null;
    }

    public function findByName(CompanyName $name): ?Company
    {
        foreach ($this->store as $company) {
            if ($company->name->value === $name->value) {
                return $company;
            }
        }

        return null;
    }

    public function findPaginated(int $limit, int $offset): array
    {
        $companies = array_values($this->store);
        usort($companies, static fn(Company $a, Company $b): int => strcmp($a->name->value, $b->name->value));

        return array_slice($companies, $offset, $limit);
    }

    public function count(): int
    {
        return count($this->store);
    }

    public function delete(CompanyId $id): void
    {
        unset($this->store[$id->value]);
    }
}
