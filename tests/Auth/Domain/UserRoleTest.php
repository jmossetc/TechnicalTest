<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Auth\Domain;

use Mossetc\TechnicalTest\Auth\Domain\Model\Role;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserRole;
use PHPUnit\Framework\TestCase;

final class UserRoleTest extends TestCase
{
    public function testAdminRoleHasNoScope(): void
    {
        $userRole = new UserRole(Role::Admin);

        self::assertSame(Role::Admin, $userRole->role);
        self::assertNull($userRole->companyId);
        self::assertNull($userRole->shopId);
    }

    public function testCompanyAdminRoleHoldsCompanyId(): void
    {
        $companyId = '11111111-1111-4111-8111-111111111111';
        $userRole  = new UserRole(Role::CompanyAdmin, companyId: $companyId);

        self::assertSame(Role::CompanyAdmin, $userRole->role);
        self::assertSame($companyId, $userRole->companyId);
        self::assertNull($userRole->shopId);
    }

    public function testShopManagerRoleHoldsShopId(): void
    {
        $shopId   = 'aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
        $userRole = new UserRole(Role::ShopManager, shopId: $shopId);

        self::assertSame(Role::ShopManager, $userRole->role);
        self::assertNull($userRole->companyId);
        self::assertSame($shopId, $userRole->shopId);
    }

    public function testEmployeeRoleHasNoScope(): void
    {
        $userRole = new UserRole(Role::Employee);

        self::assertSame(Role::Employee, $userRole->role);
        self::assertNull($userRole->companyId);
        self::assertNull($userRole->shopId);
    }

    public function testCanHoldBothCompanyIdAndShopId(): void
    {
        $companyId = '11111111-1111-4111-8111-111111111111';
        $shopId    = 'aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
        $userRole  = new UserRole(Role::ShopManager, companyId: $companyId, shopId: $shopId);

        self::assertSame($companyId, $userRole->companyId);
        self::assertSame($shopId, $userRole->shopId);
    }
}
