<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Domain;

interface UserRoleRepositoryInterface
{
    /** @return list<UserRole> */
    public function findByUserId(UserId $userId): array;

    public function grantRole(UserId $userId, UserRole $role): void;

    /** Returns the company_id that owns the given shop, or null if the shop does not exist. */
    public function findCompanyIdByShopId(string $shopId): ?string;

    /**
     * Returns the IDs of all users who are either company managers of the given
     * companies, or shop managers of shops that belong to those companies.
     *
     * @param  list<string> $companyIds
     * @return list<string>
     */
    public function findUserIdsByCompanyIds(array $companyIds): array;

    /**
     * Returns the IDs of all users who are shop managers of the given shops.
     *
     * @param  list<string> $shopIds
     * @return list<string>
     */
    public function findUserIdsByShopIds(array $shopIds): array;
}
