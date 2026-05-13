<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Domain\Repository;

use Mossetc\TechnicalTest\Auth\Domain\Model\Email;
use Mossetc\TechnicalTest\Auth\Domain\Model\User;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserId;

interface UserRepositoryInterface
{
    public function save(User $user): void;

    public function findByEmail(Email $email): ?User;

    public function findById(UserId $id): ?User;

    public function delete(UserId $id): void;

    public function updateLastLogin(UserId $id): void;

    // ── Listing ───────────────────────────────────────────────────────────────

    /** @return list<User> */
    public function findPaginated(int $limit, int $offset): array;

    public function count(): int;

    /**
     * @param  list<string> $companyIds
     * @return list<User>
     */
    public function findPaginatedByCompanyIds(array $companyIds, int $limit, int $offset): array;

    /** @param list<string> $companyIds */
    public function countByCompanyIds(array $companyIds): int;

    /**
     * @param  list<string> $shopIds
     * @return list<User>
     */
    public function findPaginatedByShopIds(array $shopIds, ?string $companyId, int $limit, int $offset): array;

    /** @param list<string> $shopIds */
    public function countByShopIds(array $shopIds, ?string $companyId): int;
}
