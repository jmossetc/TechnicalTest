<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Domain\Service;

use Mossetc\TechnicalTest\Auth\Domain\Model\Role;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserId;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserScope;
use Mossetc\TechnicalTest\Auth\Domain\Exception\ForbiddenException;
use Mossetc\TechnicalTest\Auth\Domain\Repository\UserRoleRepositoryInterface;

final readonly class UserAuthorization
{
    public function __construct(private UserRoleRepositoryInterface $roleRepository) {}

    /**
     * Assert that $callerId may register a new account with the given role and scope.
     *
     * @throws ForbiddenException
     */
    public function authorizeRegistration(
        UserId $callerId,
        ?Role $targetRole,
        ?string $targetCompanyId,
        ?string $targetShopId,
    ): void {
        $callerRoles = $this->roleRepository->findByUserId($callerId);

        if ($callerRoles === []) {
            throw new ForbiddenException('You do not have permission to create accounts');
        }

        $caller = $this->resolveCallerProfile($callerRoles);

        if ($caller['isAdmin']) {
            return;
        }

        if ($targetRole === Role::Admin) {
            throw new ForbiddenException('Only admins can create admin accounts');
        }

        if ($caller['managedCompanyIds'] !== []) {
            if ($targetRole === Role::CompanyManager) {
                if ($targetCompanyId !== null && in_array($targetCompanyId, $caller['managedCompanyIds'], true)) {
                    return;
                }
                throw new ForbiddenException('You can only create company managers for your own companies');
            }

            if ($targetRole === Role::ShopManager && $targetShopId !== null) {
                if ($this->shopBelongsToAnyCompany($targetShopId, $caller['managedCompanyIds'])) {
                    return;
                }
                throw new ForbiddenException('You can only create shop managers for shops belonging to your companies');
            }

            if ($targetRole === null) {
                return;
            }
        }

        if ($caller['managedShopIds'] !== []) {
            if ($targetRole === Role::ShopManager && $targetShopId !== null) {
                if (in_array($targetShopId, $caller['managedShopIds'], true)) {
                    return;
                }
                throw new ForbiddenException('You can only create shop managers for your own shops');
            }

            if ($targetRole === Role::CompanyManager) {
                throw new ForbiddenException('Shop managers cannot create company manager accounts');
            }

            if ($targetRole === null) {
                return;
            }
        }

        throw new ForbiddenException();
    }

    /**
     * Assert that $callerId may delete the account identified by $targetId.
     *
     * @throws ForbiddenException
     */
    public function authorizeDeletion(UserId $callerId, UserId $targetId): void
    {
        $caller = $this->resolveCallerProfile($this->roleRepository->findByUserId($callerId));
        $target = $this->resolveTargetProfile($this->roleRepository->findByUserId($targetId));

        if ($target['isAdmin']) {
            throw new ForbiddenException('And admin account cannot be deleted');
        }

        if ($caller['isAdmin']) {
            return;
        }

        if ($target['isCompanyManager']) {
            throw new ForbiddenException('Only an admin can delete a company manager');
        }

        if ($target['isShopManager']) {
            if ($caller['managedCompanyIds'] !== []) {
                foreach ($target['shopIds'] as $shopId) {
                    if ($this->shopBelongsToAnyCompany($shopId, $caller['managedCompanyIds'])) {
                        return;
                    }
                }
                throw new ForbiddenException('You can only delete shop managers for shops belonging to your companies');
            }
            throw new ForbiddenException();
        }

        throw new ForbiddenException();
    }

    /**
     * Determine what scope $callerId is allowed to list users in, applying any
     * optional caller-supplied filters within that allowed scope.
     *
     * - Admin:           UserScope::all()              (no filter)
     *                    UserScope::companies($ids)    (admin-supplied company filter)
     * - CompanyManager:  UserScope::companies($managed) (no shop filter)
     *                    UserScope::shops($filtered)   (shop filter, validated against managed companies)
     * - ShopManager:     UserScope::shops($managed)    (always restricted to own shops)
     *
     * @param list<string> $requestedCompanyIds Admin-supplied filter; ignored for non-admins
     * @param list<string> $requestedShopIds    Company-manager-supplied filter; ignored for others
     * @throws ForbiddenException when the caller has no listing permission
     */
    public function resolveListingScope(
        UserId $callerId,
        array $requestedCompanyIds = [],
        array $requestedShopIds = [],
    ): UserScope {
        $caller = $this->resolveCallerProfile($this->roleRepository->findByUserId($callerId));

        if ($caller['isAdmin']) {
            return $requestedCompanyIds !== []
                ? UserScope::companies($requestedCompanyIds)
                : UserScope::all();
        }

        if ($caller['managedCompanyIds'] !== []) {
            if ($requestedShopIds !== []) {
                $validShopIds = array_values(array_filter(
                    $requestedShopIds,
                    fn(string $shopId): bool => $this->shopBelongsToAnyCompany($shopId, $caller['managedCompanyIds']),
                ));

                if ($validShopIds !== []) {
                    return UserScope::shops($validShopIds);
                }
            }

            return UserScope::companies($caller['managedCompanyIds']);
        }

        if ($caller['managedShopIds'] !== []) {
            return UserScope::shops($caller['managedShopIds']);
        }

        throw new ForbiddenException('You do not have permission to list users');
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Summarize a user's own role set into the three capabilities used by both
     * authorizeRegistration and authorizeDeletion.
     *
     * @param  list<\Mossetc\TechnicalTest\Auth\Domain\Model\UserRole> $roles
     * @return array{isAdmin: bool, managedCompanyIds: list<string>, managedShopIds: list<string>}
     */
    private function resolveCallerProfile(array $roles): array
    {
        $isAdmin           = false;
        $managedCompanyIds = [];
        $managedShopIds    = [];

        foreach ($roles as $role) {
            if ($role->role === Role::Admin) {
                $isAdmin = true;
            }
            if ($role->role === Role::CompanyManager && $role->companyId !== null) {
                $managedCompanyIds[] = $role->companyId;
            }
            if ($role->role === Role::ShopManager && $role->shopId !== null) {
                $managedShopIds[] = $role->shopId;
            }
        }

        return ['isAdmin' => $isAdmin, 'managedCompanyIds' => $managedCompanyIds, 'managedShopIds' => $managedShopIds];
    }

    /**
     * Summarize a target user's role set into the shape used by authorizeDeletion.
     *
     * @param  list<\Mossetc\TechnicalTest\Auth\Domain\Model\UserRole> $roles
     * @return array{isAdmin: bool, isCompanyManager: bool, isShopManager: bool, shopIds: list<string>}
     */
    private function resolveTargetProfile(array $roles): array
    {
        $isAdmin          = false;
        $isCompanyManager = false;
        $isShopManager    = false;
        $shopIds          = [];

        foreach ($roles as $role) {
            if ($role->role === Role::Admin) {
                $isAdmin = true;
            }
            if ($role->role === Role::CompanyManager) {
                $isCompanyManager = true;
            }
            if ($role->role === Role::ShopManager) {
                $isShopManager = true;
                if ($role->shopId !== null) {
                    $shopIds[] = $role->shopId;
                }
            }
        }

        return ['isAdmin' => $isAdmin, 'isCompanyManager' => $isCompanyManager, 'isShopManager' => $isShopManager, 'shopIds' => $shopIds];
    }

    /**
     * Returns true when $shopId's owning company is among $managedCompanyIds.
     * Encapsulates the findCompanyIdByShopId + in_array pair used in both public methods.
     *
     * @param list<string> $managedCompanyIds
     */
    private function shopBelongsToAnyCompany(string $shopId, array $managedCompanyIds): bool
    {
        $shopCompanyId = $this->roleRepository->findCompanyIdByShopId($shopId);

        return $shopCompanyId !== null && in_array($shopCompanyId, $managedCompanyIds, true);
    }
}
