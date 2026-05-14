<?php

declare(strict_types=1);

use Mossetc\TechnicalTest\Company\Domain\Model\Company;
use Mossetc\TechnicalTest\Company\Domain\Model\CompanyId;
use Mossetc\TechnicalTest\Company\Domain\Model\CompanyName;
use Mossetc\TechnicalTest\Company\Domain\Model\CompanySearchCriteria;
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

    public function findPaginatedByCriteria(CompanySearchCriteria $criteria, int $limit, int $offset): array
    {
        $companies = array_values($this->filteredByCriteria($criteria));
        usort($companies, static fn(Company $a, Company $b): int => strcmp($a->name->value, $b->name->value));

        return array_slice($companies, $offset, $limit);
    }

    public function countByCriteria(CompanySearchCriteria $criteria): int
    {
        return count($this->filteredByCriteria($criteria));
    }

    public function delete(CompanyId $id): void
    {
        unset($this->store[$id->value]);
    }

    /** @return array<string, Company> */
    private function filteredByCriteria(CompanySearchCriteria $criteria): array
    {
        return array_filter($this->store, static function (Company $c) use ($criteria): bool {
            if ($criteria->name !== null && stripos($c->name->value, $criteria->name) === false) {
                return false;
            }

            if ($criteria->email !== null
                && ($c->email === null || stripos($c->email, $criteria->email) === false)) {
                return false;
            }

            if ($criteria->phoneNumber !== null
                && ($c->phoneNumber === null || stripos($c->phoneNumber, $criteria->phoneNumber) === false)) {
                return false;
            }

            if ($criteria->city !== null
                && ($c->city === null || stripos($c->city, $criteria->city) === false)) {
                return false;
            }

            if ($criteria->postalCode !== null
                && ($c->postalCode === null || stripos($c->postalCode, $criteria->postalCode) === false)) {
                return false;
            }

            if ($criteria->country !== null
                && ($c->country === null || stripos($c->country, $criteria->country) === false)) {
                return false;
            }

            if ($criteria->createdFrom !== null && $c->createdAt < $criteria->createdFrom) {
                return false;
            }

            if ($criteria->createdTo !== null && $c->createdAt > $criteria->createdTo) {
                return false;
            }

            return true;
        });
    }
}
