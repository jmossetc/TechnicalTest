<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Support;

use Mossetc\TechnicalTest\Auth\Domain\Role;
use Mossetc\TechnicalTest\Auth\Domain\UserId;
use Mossetc\TechnicalTest\Auth\Domain\UserRole;
use Mossetc\TechnicalTest\Auth\Domain\UserRoleRepositoryInterface;

final class InMemoryUserRoleRepository implements UserRoleRepositoryInterface
{
    /** @var list<array{userId: string, role: UserRole}> */
    private array $grants = [];

    /** @var array<string, string> shopId => companyId */
    private array $shopCompanyMap = [];

    public function findByUserId(UserId $userId): array
    {
        $roles = [];
        foreach ($this->grants as $grant) {
            if ($grant['userId'] === $userId->value) {
                $roles[] = $grant['role'];
            }
        }

        return $roles;
    }

    public function grantRole(UserId $userId, UserRole $role): void
    {
        $this->grants[] = ['userId' => $userId->value, 'role' => $role];
    }

    public function findCompanyIdByShopId(string $shopId): ?string
    {
        return $this->shopCompanyMap[$shopId] ?? null;
    }

    public function findUserIdsByCompanyIds(array $companyIds): array
    {
        $ids = [];
        foreach ($this->grants as $grant) {
            $role = $grant['role'];
            if ($role->role === Role::CompanyManager
                && $role->companyId !== null
                && in_array($role->companyId, $companyIds, true)
            ) {
                $ids[] = $grant['userId'];
            }
            if ($role->role === Role::ShopManager && $role->shopId !== null) {
                $companyId = $this->shopCompanyMap[$role->shopId] ?? null;
                if ($companyId !== null && in_array($companyId, $companyIds, true)) {
                    $ids[] = $grant['userId'];
                }
            }
        }

        return array_values(array_unique($ids));
    }

    public function findUserIdsByShopIds(array $shopIds): array
    {
        $ids = [];
        foreach ($this->grants as $grant) {
            $role = $grant['role'];
            if ($role->role === Role::ShopManager
                && $role->shopId !== null
                && in_array($role->shopId, $shopIds, true)
            ) {
                $ids[] = $grant['userId'];
            }
        }

        return array_values(array_unique($ids));
    }

    public function registerShop(string $shopId, string $companyId): void
    {
        $this->shopCompanyMap[$shopId] = $companyId;
    }
}
