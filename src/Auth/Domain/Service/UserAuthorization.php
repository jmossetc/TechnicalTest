<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Domain\Service;

use Mossetc\TechnicalTest\Auth\Domain\Exception\ForbiddenException;
use Mossetc\TechnicalTest\Auth\Domain\Model\Role;
use Mossetc\TechnicalTest\Auth\Domain\Model\User;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserId;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserScope;
use Mossetc\TechnicalTest\Auth\Domain\Repository\UserRepositoryInterface;

final readonly class UserAuthorization
{
    public function __construct(private UserRepositoryInterface $userRepository) {}

    /**
     * Assert that $callerId may register a new account.
     *
     * For shop_manager targets, $targetCompanyId must be the shop's company (resolved by caller).
     *
     * @throws ForbiddenException
     */
    public function authorizeRegistration(
        UserId $callerId,
        Role $targetRole,
        ?string $targetCompanyId,
        ?string $targetShopId,
    ): void {
        $caller = $this->loadCaller($callerId);

        if ($caller->role === Role::Admin) {
            return;
        }

        if ($targetRole === Role::Admin) {
            throw new ForbiddenException('Only admins can create admin accounts');
        }

        if ($caller->role === Role::CompanyAdmin) {
            if ($targetRole === Role::CompanyAdmin) {
                if ($targetCompanyId !== null && $targetCompanyId === $caller->companyId) {
                    return;
                }
                throw new ForbiddenException('You can only create company admins for your own company');
            }

            if ($targetRole === Role::ShopManager) {
                if ($targetCompanyId !== null && $targetCompanyId === $caller->companyId) {
                    return;
                }
                throw new ForbiddenException('You can only create shop managers for shops belonging to your company');
            }

            if ($targetRole === Role::Employee) {
                return;
            }
        }

        if ($caller->role === Role::ShopManager) {
            if ($targetRole === Role::CompanyAdmin) {
                throw new ForbiddenException('Shop managers cannot create company admin accounts');
            }

            if ($targetRole === Role::ShopManager) {
                if ($targetShopId !== null && $targetShopId === $caller->shopId) {
                    return;
                }
                throw new ForbiddenException('You can only create shop managers for your own shop');
            }

            if ($targetRole === Role::Employee) {
                return;
            }
        }

        throw new ForbiddenException('You do not have permission to create accounts');
    }

    /**
     * Assert that $callerId may delete $target.
     *
     * @throws ForbiddenException
     */
    public function authorizeDeletion(UserId $callerId, User $target): void
    {
        $caller = $this->loadCaller($callerId);

        if ($target->role === Role::Admin) {
            throw new ForbiddenException('Admin accounts cannot be deleted');
        }

        if ($caller->role === Role::Admin) {
            return;
        }

        if ($target->role === Role::CompanyAdmin) {
            throw new ForbiddenException('Only an admin can delete a company admin');
        }

        if ($caller->role === Role::CompanyAdmin && $caller->companyId !== null) {
            if ($caller->companyId === $target->companyId) {
                return;
            }
            throw new ForbiddenException('You can only delete users in your own company');
        }

        throw new ForbiddenException('You do not have permission to delete this account');
    }

    /**
     * Determine what scope $callerId is allowed to list users in.
     *
     * @param list<string> $requestedCompanyIds Admin-only filter
     * @param list<string> $requestedShopIds    CompanyAdmin filter; restricted to their company
     * @throws ForbiddenException
     */
    public function resolveListingScope(
        UserId $callerId,
        array $requestedCompanyIds = [],
        array $requestedShopIds = [],
    ): UserScope {
        $caller = $this->loadCaller($callerId);

        if ($caller->role === Role::Admin) {
            return $requestedCompanyIds !== []
                ? UserScope::companies($requestedCompanyIds)
                : UserScope::all();
        }

        if ($caller->role === Role::CompanyAdmin && $caller->companyId !== null) {
            if ($requestedShopIds !== []) {
                // scopeCompanyId constrains the query so cross-company shops are never returned
                return UserScope::shops($requestedShopIds, $caller->companyId);
            }

            return UserScope::companies([$caller->companyId]);
        }

        if ($caller->role === Role::ShopManager && $caller->shopId !== null) {
            return UserScope::shops([$caller->shopId]);
        }

        throw new ForbiddenException('You do not have permission to list users');
    }

    /**
     * Assert that $callerId is an admin.
     *
     * @throws ForbiddenException
     */
    public function authorizeAdminOnlyAction(UserId $callerId): void
    {
        if ($this->loadCaller($callerId)->role !== Role::Admin) {
            throw new ForbiddenException('Only admins can perform this action');
        }
    }

    /**
     * Assert that $callerId may read or edit the given company.
     *
     * @throws ForbiddenException
     */
    public function authorizeCompanyAccess(UserId $callerId, string $companyId): void
    {
        $caller = $this->loadCaller($callerId);

        if ($caller->role === Role::Admin) {
            return;
        }

        if ($caller->role === Role::CompanyAdmin && $caller->companyId === $companyId) {
            return;
        }

        throw new ForbiddenException('You do not have permission to access this company');
    }

    /**
     * Assert that $callerId may read or edit the given shop.
     *
     * @throws ForbiddenException
     */
    public function authorizeShopAccess(UserId $callerId, string $shopId, string $companyId): void
    {
        $caller = $this->loadCaller($callerId);

        if ($caller->role === Role::Admin) {
            return;
        }

        if ($caller->role === Role::CompanyAdmin && $caller->companyId === $companyId) {
            return;
        }

        if ($caller->role === Role::ShopManager && $caller->shopId === $shopId) {
            return;
        }

        throw new ForbiddenException('You do not have permission to access this shop');
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function loadCaller(UserId $id): User
    {
        $user = $this->userRepository->findById($id);

        if ($user === null || !$user->isActive) {
            throw new ForbiddenException('Caller not found or inactive');
        }

        return $user;
    }
}
