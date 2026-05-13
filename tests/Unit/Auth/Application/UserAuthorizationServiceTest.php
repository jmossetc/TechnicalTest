<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Unit\Auth\Application;

use Mossetc\TechnicalTest\Auth\Application\Service\UserAuthorizationService;
use Mossetc\TechnicalTest\Auth\Domain\Exception\ForbiddenException;
use Mossetc\TechnicalTest\Auth\Domain\Role;
use Mossetc\TechnicalTest\Auth\Domain\UserId;
use Mossetc\TechnicalTest\Auth\Domain\UserRole;
use Mossetc\TechnicalTest\Auth\Domain\UserRoleRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class UserAuthorizationServiceTest extends TestCase
{
    private const string COMPANY_A = '11111111-1111-4111-8111-111111111111';
    private const string COMPANY_B = '22222222-2222-4222-8222-222222222222';
    private const string SHOP_A1   = 'aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
    private const string SHOP_B1   = 'bbbb1111-bbbb-4bbb-8bbb-bbbbbbbbbbbb';

    private UserId $callerId;
    private UserId $targetId;

    protected function setUp(): void
    {
        $this->callerId = UserId::generate();
        $this->targetId = UserId::generate();
    }

    // ── authorizeRegistration ─────────────────────────────────────────────────

    public function testAdminCanRegisterAdmin(): void
    {
        $this->service([new UserRole(Role::Admin)])
            ->authorizeRegistration($this->callerId, Role::Admin, null, null);

        $this->addToAssertionCount(1);
    }

    public function testAdminCanRegisterCompanyManager(): void
    {
        $this->service([new UserRole(Role::Admin)])
            ->authorizeRegistration($this->callerId, Role::CompanyManager, self::COMPANY_A, null);

        $this->addToAssertionCount(1);
    }

    public function testAdminCanRegisterShopManager(): void
    {
        $this->service([new UserRole(Role::Admin)])
            ->authorizeRegistration($this->callerId, Role::ShopManager, null, self::SHOP_A1);

        $this->addToAssertionCount(1);
    }

    public function testAdminCanRegisterPlainUser(): void
    {
        $this->service([new UserRole(Role::Admin)])
            ->authorizeRegistration($this->callerId, null, null, null);

        $this->addToAssertionCount(1);
    }

    public function testUserWithNoRolesCannotRegisterAnyone(): void
    {
        $this->expectException(ForbiddenException::class);
        $this->service([])
            ->authorizeRegistration($this->callerId, null, null, null);
    }

    public function testCompanyManagerCanRegisterCompanyManagerForOwnCompany(): void
    {
        $this->service([new UserRole(Role::CompanyManager, companyId: self::COMPANY_A)])
            ->authorizeRegistration($this->callerId, Role::CompanyManager, self::COMPANY_A, null);

        $this->addToAssertionCount(1);
    }

    public function testCompanyManagerCannotRegisterCompanyManagerForOtherCompany(): void
    {
        $this->expectException(ForbiddenException::class);
        $this->service([new UserRole(Role::CompanyManager, companyId: self::COMPANY_A)])
            ->authorizeRegistration($this->callerId, Role::CompanyManager, self::COMPANY_B, null);
    }

    public function testCompanyManagerCanRegisterShopManagerForShopInOwnCompany(): void
    {
        $this->service(
            callerRoles: [new UserRole(Role::CompanyManager, companyId: self::COMPANY_A)],
            shopCompanyMap: [self::SHOP_A1 => self::COMPANY_A],
        )->authorizeRegistration($this->callerId, Role::ShopManager, null, self::SHOP_A1);

        $this->addToAssertionCount(1);
    }

    public function testCompanyManagerCannotRegisterShopManagerForShopInOtherCompany(): void
    {
        $this->expectException(ForbiddenException::class);
        $this->service(
            callerRoles: [new UserRole(Role::CompanyManager, companyId: self::COMPANY_A)],
            shopCompanyMap: [self::SHOP_B1 => self::COMPANY_B],
        )->authorizeRegistration($this->callerId, Role::ShopManager, null, self::SHOP_B1);
    }

    public function testCompanyManagerCannotRegisterAdmin(): void
    {
        $this->expectException(ForbiddenException::class);
        $this->service([new UserRole(Role::CompanyManager, companyId: self::COMPANY_A)])
            ->authorizeRegistration($this->callerId, Role::Admin, null, null);
    }

    public function testCompanyManagerCanRegisterPlainUser(): void
    {
        $this->service([new UserRole(Role::CompanyManager, companyId: self::COMPANY_A)])
            ->authorizeRegistration($this->callerId, null, null, null);

        $this->addToAssertionCount(1);
    }

    public function testShopManagerCanRegisterShopManagerForOwnShop(): void
    {
        $this->service([new UserRole(Role::ShopManager, shopId: self::SHOP_A1)])
            ->authorizeRegistration($this->callerId, Role::ShopManager, null, self::SHOP_A1);

        $this->addToAssertionCount(1);
    }

    public function testShopManagerCannotRegisterShopManagerForOtherShop(): void
    {
        $this->expectException(ForbiddenException::class);
        $this->service([new UserRole(Role::ShopManager, shopId: self::SHOP_A1)])
            ->authorizeRegistration($this->callerId, Role::ShopManager, null, self::SHOP_B1);
    }

    public function testShopManagerCannotRegisterCompanyManager(): void
    {
        $this->expectException(ForbiddenException::class);
        $this->service([new UserRole(Role::ShopManager, shopId: self::SHOP_A1)])
            ->authorizeRegistration($this->callerId, Role::CompanyManager, self::COMPANY_A, null);
    }

    public function testShopManagerCannotRegisterAdmin(): void
    {
        $this->expectException(ForbiddenException::class);
        $this->service([new UserRole(Role::ShopManager, shopId: self::SHOP_A1)])
            ->authorizeRegistration($this->callerId, Role::Admin, null, null);
    }

    public function testShopManagerCanRegisterPlainUser(): void
    {
        $this->service([new UserRole(Role::ShopManager, shopId: self::SHOP_A1)])
            ->authorizeRegistration($this->callerId, null, null, null);

        $this->addToAssertionCount(1);
    }

    // ── authorizeDeletion ─────────────────────────────────────────────────────

    public function testAdminCanDeletePlainUser(): void
    {
        $this->service(callerRoles: [new UserRole(Role::Admin)], targetRoles: [])
            ->authorizeDeletion($this->callerId, $this->targetId);

        $this->addToAssertionCount(1);
    }

    public function testAdminCanDeleteShopManager(): void
    {
        $this->service(
            callerRoles: [new UserRole(Role::Admin)],
            targetRoles: [new UserRole(Role::ShopManager, shopId: self::SHOP_A1)],
        )->authorizeDeletion($this->callerId, $this->targetId);

        $this->addToAssertionCount(1);
    }

    public function testAdminCanDeleteCompanyManager(): void
    {
        $this->service(
            callerRoles: [new UserRole(Role::Admin)],
            targetRoles: [new UserRole(Role::CompanyManager, companyId: self::COMPANY_A)],
        )->authorizeDeletion($this->callerId, $this->targetId);

        $this->addToAssertionCount(1);
    }

    public function testNobodyCanDeleteAdmin(): void
    {
        $this->expectException(ForbiddenException::class);
        $this->service(
            callerRoles: [new UserRole(Role::Admin)],
            targetRoles: [new UserRole(Role::Admin)],
        )->authorizeDeletion($this->callerId, $this->targetId);
    }

    public function testCompanyManagerCanDeleteShopManagerInOwnCompany(): void
    {
        $this->service(
            callerRoles: [new UserRole(Role::CompanyManager, companyId: self::COMPANY_A)],
            targetRoles: [new UserRole(Role::ShopManager, shopId: self::SHOP_A1)],
            shopCompanyMap: [self::SHOP_A1 => self::COMPANY_A],
        )->authorizeDeletion($this->callerId, $this->targetId);

        $this->addToAssertionCount(1);
    }

    public function testCompanyManagerCannotDeleteShopManagerInOtherCompany(): void
    {
        $this->expectException(ForbiddenException::class);
        $this->service(
            callerRoles: [new UserRole(Role::CompanyManager, companyId: self::COMPANY_A)],
            targetRoles: [new UserRole(Role::ShopManager, shopId: self::SHOP_B1)],
            shopCompanyMap: [self::SHOP_B1 => self::COMPANY_B],
        )->authorizeDeletion($this->callerId, $this->targetId);
    }

    public function testCompanyManagerCannotDeleteCompanyManager(): void
    {
        $this->expectException(ForbiddenException::class);
        $this->service(
            callerRoles: [new UserRole(Role::CompanyManager, companyId: self::COMPANY_A)],
            targetRoles: [new UserRole(Role::CompanyManager, companyId: self::COMPANY_A)],
        )->authorizeDeletion($this->callerId, $this->targetId);
    }

    public function testCompanyManagerCannotDeleteAdmin(): void
    {
        $this->expectException(ForbiddenException::class);
        $this->service(
            callerRoles: [new UserRole(Role::CompanyManager, companyId: self::COMPANY_A)],
            targetRoles: [new UserRole(Role::Admin)],
        )->authorizeDeletion($this->callerId, $this->targetId);
    }

    public function testShopManagerCannotDeleteAnyone(): void
    {
        $this->expectException(ForbiddenException::class);
        $this->service(
            callerRoles: [new UserRole(Role::ShopManager, shopId: self::SHOP_A1)],
            targetRoles: [],
        )->authorizeDeletion($this->callerId, $this->targetId);
    }

    public function testPlainUserCannotDeleteAnyone(): void
    {
        $this->expectException(ForbiddenException::class);
        $this->service(callerRoles: [], targetRoles: [])
            ->authorizeDeletion($this->callerId, $this->targetId);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Build a service instance with a mock repository that returns the given
     * roles per user ID and resolves shop→company from the optional map.
     *
     * @param list<UserRole>        $callerRoles
     * @param list<UserRole>        $targetRoles
     * @param array<string, string> $shopCompanyMap shop_id → company_id
     */
    private function service(
        array $callerRoles = [],
        array $targetRoles = [],
        array $shopCompanyMap = [],
    ): UserAuthorizationService {
        $callerId = $this->callerId;
        $targetId = $this->targetId;

        $repo = $this->createStub(UserRoleRepositoryInterface::class);

        $repo->method('findByUserId')
            ->willReturnCallback(
                static function (UserId $id) use ($callerId, $targetId, $callerRoles, $targetRoles): array {
                    if ($id->equals($callerId)) {
                        return $callerRoles;
                    }
                    if ($id->equals($targetId)) {
                        return $targetRoles;
                    }
                    return [];
                },
            );

        $repo->method('findCompanyIdByShopId')
            ->willReturnCallback(
                static fn(string $shopId): ?string => $shopCompanyMap[$shopId] ?? null,
            );

        return new UserAuthorizationService($repo);
    }
}
