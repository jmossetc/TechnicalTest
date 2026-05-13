<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Support;

use Mossetc\TechnicalTest\Company\Domain\Model\Company;
use Mossetc\TechnicalTest\Company\Domain\Model\CompanyId;
use Mossetc\TechnicalTest\Company\Domain\Model\CompanyName;
use Mossetc\TechnicalTest\Company\Domain\Repository\CompanyRepositoryInterface;

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

    public function findPaginated(int $limit, int $offset, ?string $name = null): array
    {
        $companies = array_values($this->filtered($name));
        usort($companies, static fn(Company $a, Company $b): int => strcmp($a->name->value, $b->name->value));

        return array_slice($companies, $offset, $limit);
    }

    public function count(?string $name = null): int
    {
        return count($this->filtered($name));
    }

    public function delete(CompanyId $id): void
    {
        unset($this->store[$id->value]);
    }

    /** @return array<string, Company> */
    private function filtered(?string $name): array
    {
        if ($name === null) {
            return $this->store;
        }

        return array_filter(
            $this->store,
            static fn(Company $c): bool => stripos($c->name->value, $name) !== false,
        );
    }
}
