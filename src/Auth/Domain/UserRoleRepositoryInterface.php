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
}
