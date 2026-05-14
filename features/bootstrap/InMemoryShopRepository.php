<?php

declare(strict_types=1);

use Mossetc\TechnicalTest\Company\Domain\Model\CompanyId;
use Mossetc\TechnicalTest\Shop\Domain\Model\Shop;
use Mossetc\TechnicalTest\Shop\Domain\Model\ShopId;
use Mossetc\TechnicalTest\Shop\Domain\Model\ShopName;
use Mossetc\TechnicalTest\Shop\Domain\Model\ShopSearchCriteria;
use Mossetc\TechnicalTest\Shop\Domain\Model\ShopSortCriteria;
use Mossetc\TechnicalTest\Shop\Domain\Model\ShopSortField;
use Mossetc\TechnicalTest\Shop\Domain\Repository\ShopRepositoryInterface;
use Mossetc\TechnicalTest\Shared\Domain\SortDirection;

/**
 * In-memory implementation of ShopRepositoryInterface for Behat scenarios.
 * Lives in features/bootstrap/ so it is never shipped to production.
 */
final class InMemoryShopRepository implements ShopRepositoryInterface
{
    /** @var array<string, Shop> */
    private array $store = [];

    public function save(Shop $shop): void
    {
        $this->store[$shop->id->value] = $shop;
    }

    public function findById(ShopId $id): ?Shop
    {
        return $this->store[$id->value] ?? null;
    }

    public function findByNameAndCompany(ShopName $name, CompanyId $companyId): ?Shop
    {
        foreach ($this->store as $shop) {
            if ($shop->name->value === $name->value && $shop->companyId->value === $companyId->value) {
                return $shop;
            }
        }

        return null;
    }

    public function findPaginatedByCriteria(
        ShopSearchCriteria $criteria,
        ShopSortCriteria   $sort,
        int                $limit,
        int                $offset,
    ): array {
        $shops = array_values($this->filteredByCriteria($criteria));
        usort($shops, $this->buildComparator($sort));

        return array_slice($shops, $offset, $limit);
    }

    public function countByCriteria(ShopSearchCriteria $criteria): int
    {
        return count($this->filteredByCriteria($criteria));
    }

    public function delete(ShopId $id): void
    {
        unset($this->store[$id->value]);
    }

    private function buildComparator(ShopSortCriteria $sort): callable
    {
        return static function (Shop $a, Shop $b) use ($sort): int {
            $cmp = match ($sort->field) {
                ShopSortField::CompanyId  => strcmp($a->companyId->value, $b->companyId->value),
                ShopSortField::Name       => strcmp($a->name->value, $b->name->value),
                ShopSortField::Email      => strcmp($a->email ?? '', $b->email ?? ''),
                ShopSortField::City       => strcmp($a->address->city ?? '', $b->address->city ?? ''),
                ShopSortField::PostalCode => strcmp($a->address->postalCode ?? '', $b->address->postalCode ?? ''),
                ShopSortField::Country    => strcmp($a->address->country ?? '', $b->address->country ?? ''),
                ShopSortField::IsActive   => (int) $a->isActive <=> (int) $b->isActive,
                ShopSortField::CreatedAt  => $a->createdAt <=> $b->createdAt,
                ShopSortField::UpdatedAt  => $a->updatedAt <=> $b->updatedAt,
            };

            return $sort->direction === SortDirection::Desc ? -$cmp : $cmp;
        };
    }

    /** @return array<string, Shop> */
    private function filteredByCriteria(ShopSearchCriteria $criteria): array
    {
        return array_filter($this->store, static function (Shop $s) use ($criteria): bool {
            if ($criteria->companyId !== null && $s->companyId->value !== $criteria->companyId) {
                return false;
            }

            if ($criteria->name !== null && stripos($s->name->value, $criteria->name) === false) {
                return false;
            }

            if ($criteria->email !== null
                && ($s->email === null || stripos($s->email, $criteria->email) === false)) {
                return false;
            }

            if ($criteria->phoneNumber !== null
                && ($s->phoneNumber === null || stripos($s->phoneNumber, $criteria->phoneNumber) === false)) {
                return false;
            }

            if ($criteria->city !== null
                && ($s->address->city === null || stripos($s->address->city, $criteria->city) === false)) {
                return false;
            }

            if ($criteria->postalCode !== null
                && ($s->address->postalCode === null || stripos($s->address->postalCode, $criteria->postalCode) === false)) {
                return false;
            }

            if ($criteria->country !== null
                && ($s->address->country === null || stripos($s->address->country, $criteria->country) === false)) {
                return false;
            }

            if ($criteria->isDigital !== null && $s->isDigital !== $criteria->isDigital) {
                return false;
            }

            if ($criteria->createdFrom !== null && $s->createdAt < $criteria->createdFrom) {
                return false;
            }

            if ($criteria->createdTo !== null && $s->createdAt > $criteria->createdTo) {
                return false;
            }

            return true;
        });
    }
}
