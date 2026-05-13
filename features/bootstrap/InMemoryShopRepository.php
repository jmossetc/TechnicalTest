<?php

declare(strict_types=1);

use Mossetc\TechnicalTest\Company\Domain\Model\CompanyId;
use Mossetc\TechnicalTest\Shop\Domain\Model\Shop;
use Mossetc\TechnicalTest\Shop\Domain\Model\ShopId;
use Mossetc\TechnicalTest\Shop\Domain\Model\ShopName;
use Mossetc\TechnicalTest\Shop\Domain\Repository\ShopRepositoryInterface;

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

    public function findPaginatedByCompany(CompanyId $companyId, int $limit, int $offset): array
    {
        $shops = array_values(array_filter(
            $this->store,
            static fn(Shop $s): bool => $s->companyId->value === $companyId->value,
        ));
        usort($shops, static fn(Shop $a, Shop $b): int => strcmp($a->name->value, $b->name->value));

        return array_slice($shops, $offset, $limit);
    }

    public function countByCompany(CompanyId $companyId): int
    {
        return count(array_filter(
            $this->store,
            static fn(Shop $s): bool => $s->companyId->value === $companyId->value,
        ));
    }

    public function delete(ShopId $id): void
    {
        unset($this->store[$id->value]);
    }
}
