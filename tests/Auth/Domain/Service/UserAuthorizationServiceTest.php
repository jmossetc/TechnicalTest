<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Auth\Domain\Service;

use Mossetc\TechnicalTest\Auth\Domain\Exception\ForbiddenException;
use Mossetc\TechnicalTest\Auth\Domain\Model\Email;
use Mossetc\TechnicalTest\Auth\Domain\Model\FirstName;
use Mossetc\TechnicalTest\Auth\Domain\Model\HashedPassword;
use Mossetc\TechnicalTest\Auth\Domain\Model\LastName;
use Mossetc\TechnicalTest\Auth\Domain\Model\PlainPassword;
use Mossetc\TechnicalTest\Auth\Domain\Model\Role;
use Mossetc\TechnicalTest\Auth\Domain\Model\User;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserId;
use Mossetc\TechnicalTest\Auth\Domain\Repository\UserRepositoryInterface;
use Mossetc\TechnicalTest\Auth\Domain\Service\UserAuthorizationService;
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
        $this->service(callerRole: Role::Admin)
            ->authorizeRegistration($this->callerId, Role::Admin, null, null);
        $this->addToAssertionCount(1);
    }

    public function testAdminCanRegisterCompanyAdmin(): void
    {
        $this->service(callerRole: Role::Admin)
            ->authorizeRegistration($this->callerId, Role::CompanyAdmin, self::COMPANY_A, null);
        $this->addToAssertionCount(1);
    }

    public function testAdminCanRegisterShopManager(): void
    {
        $this->service(callerRole: Role::Admin)
            ->authorizeRegistration($this->callerId, Role::ShopManager, self::COMPANY_A, self::SHOP_A1);
        $this->addToAssertionCount(1);
    }

    public function testAdminCanRegisterEmployee(): void
    {
        $this->service(callerRole: Role::Admin)
            ->authorizeRegistration($this->callerId, Role::Employee, null, null);
        $this->addToAssertionCount(1);
    }

    public function testEmployeeCannotRegisterAnyone(): void
    {
        $this->expectException(ForbiddenException::class);
        $this->service(callerRole: Role::Employee)
            ->authorizeRegistration($this->callerId, Role::Employee, null, null);
    }

    public function testCompanyAdminCanRegisterCompanyAdminForOwnCompany(): void
    {
        $this->service(callerRole: Role::CompanyAdmin, callerCompanyId: self::COMPANY_A)
            ->authorizeRegistration($this->callerId, Role::CompanyAdmin, self::COMPANY_A, null);
        $this->addToAssertionCount(1);
    }

    public function testCompanyAdminCannotRegisterCompanyAdminForOtherCompany(): void
    {
        $this->expectException(ForbiddenException::class);
        $this->service(callerRole: Role::CompanyAdmin, callerCompanyId: self::COMPANY_A)
            ->authorizeRegistration($this->callerId, Role::CompanyAdmin, self::COMPANY_B, null);
    }

    public function testCompanyAdminCanRegisterShopManagerForOwnCompany(): void
    {
        // targetCompanyId = shop's company (already resolved by controller)
        $this->service(callerRole: Role::CompanyAdmin, callerCompanyId: self::COMPANY_A)
            ->authorizeRegistration($this->callerId, Role::ShopManager, self::COMPANY_A, self::SHOP_A1);
        $this->addToAssertionCount(1);
    }

    public function testCompanyAdminCannotRegisterShopManagerForOtherCompany(): void
    {
        $this->expectException(ForbiddenException::class);
        $this->service(callerRole: Role::CompanyAdmin, callerCompanyId: self::COMPANY_A)
            ->authorizeRegistration($this->callerId, Role::ShopManager, self::COMPANY_B, self::SHOP_B1);
    }

    public function testCompanyAdminCannotRegisterAdmin(): void
    {
        $this->expectException(ForbiddenException::class);
        $this->service(callerRole: Role::CompanyAdmin, callerCompanyId: self::COMPANY_A)
            ->authorizeRegistration($this->callerId, Role::Admin, null, null);
    }

    public function testCompanyAdminCanRegisterEmployee(): void
    {
        $this->service(callerRole: Role::CompanyAdmin, callerCompanyId: self::COMPANY_A)
            ->authorizeRegistration($this->callerId, Role::Employee, null, null);
        $this->addToAssertionCount(1);
    }

    public function testShopManagerCanRegisterShopManagerForOwnShop(): void
    {
        $this->service(callerRole: Role::ShopManager, callerShopId: self::SHOP_A1)
            ->authorizeRegistration($this->callerId, Role::ShopManager, null, self::SHOP_A1);
        $this->addToAssertionCount(1);
    }

    public function testShopManagerCannotRegisterShopManagerForOtherShop(): void
    {
        $this->expectException(ForbiddenException::class);
        $this->service(callerRole: Role::ShopManager, callerShopId: self::SHOP_A1)
            ->authorizeRegistration($this->callerId, Role::ShopManager, null, self::SHOP_B1);
    }

    public function testShopManagerCannotRegisterCompanyAdmin(): void
    {
        $this->expectException(ForbiddenException::class);
        $this->service(callerRole: Role::ShopManager, callerShopId: self::SHOP_A1)
            ->authorizeRegistration($this->callerId, Role::CompanyAdmin, self::COMPANY_A, null);
    }

    public function testShopManagerCanRegisterEmployee(): void
    {
        $this->service(callerRole: Role::ShopManager, callerShopId: self::SHOP_A1)
            ->authorizeRegistration($this->callerId, Role::Employee, null, null);
        $this->addToAssertionCount(1);
    }

    public function testInactiveUserCannotRegisterAccount(): void
    {
        $this->expectException(ForbiddenException::class);
        $this->service(callerRole: Role::Admin, isUserActive: false)
            ->authorizeRegistration($this->callerId, Role::Admin, null, null);
    }


    // ── authorizeDeletion ─────────────────────────────────────────────────────

    public function testAdminCanDeleteEmployee(): void
    {
        $target = $this->makeUser($this->targetId, Role::Employee);
        $this->service(callerRole: Role::Admin)->authorizeDeletion($this->callerId, $target);
        $this->addToAssertionCount(1);
    }

    public function testAdminCanDeleteShopManager(): void
    {
        $target = $this->makeUser($this->targetId, Role::ShopManager, shopId: self::SHOP_A1);
        $this->service(callerRole: Role::Admin)->authorizeDeletion($this->callerId, $target);
        $this->addToAssertionCount(1);
    }

    public function testAdminCanDeleteCompanyAdmin(): void
    {
        $target = $this->makeUser($this->targetId, Role::CompanyAdmin, companyId: self::COMPANY_A);
        $this->service(callerRole: Role::Admin)->authorizeDeletion($this->callerId, $target);
        $this->addToAssertionCount(1);
    }

    public function testNobodyCanDeleteAdmin(): void
    {
        $target = $this->makeUser($this->targetId, Role::Admin);
        $this->expectException(ForbiddenException::class);
        $this->service(callerRole: Role::Admin)->authorizeDeletion($this->callerId, $target);
    }

    public function testCompanyAdminCanDeleteShopManagerInOwnCompany(): void
    {
        $target = $this->makeUser($this->targetId, Role::ShopManager, companyId: self::COMPANY_A, shopId: self::SHOP_A1);
        $this->service(callerRole: Role::CompanyAdmin, callerCompanyId: self::COMPANY_A)
            ->authorizeDeletion($this->callerId, $target);
        $this->addToAssertionCount(1);
    }

    public function testCompanyAdminCannotDeleteShopManagerInOtherCompany(): void
    {
        $target = $this->makeUser($this->targetId, Role::ShopManager, companyId: self::COMPANY_B, shopId: self::SHOP_B1);
        $this->expectException(ForbiddenException::class);
        $this->service(callerRole: Role::CompanyAdmin, callerCompanyId: self::COMPANY_A)
            ->authorizeDeletion($this->callerId, $target);
    }

    public function testCompanyAdminCannotDeleteCompanyAdmin(): void
    {
        $target = $this->makeUser($this->targetId, Role::CompanyAdmin, companyId: self::COMPANY_A);
        $this->expectException(ForbiddenException::class);
        $this->service(callerRole: Role::CompanyAdmin, callerCompanyId: self::COMPANY_A)
            ->authorizeDeletion($this->callerId, $target);
    }

    public function testShopManagerCannotDeleteAnyone(): void
    {
        $target = $this->makeUser($this->targetId, Role::Employee);
        $this->expectException(ForbiddenException::class);
        $this->service(callerRole: Role::ShopManager, callerShopId: self::SHOP_A1)
            ->authorizeDeletion($this->callerId, $target);
    }

    public function testEmployeeCannotDeleteAnyone(): void
    {
        $target = $this->makeUser($this->targetId, Role::Employee);
        $this->expectException(ForbiddenException::class);
        $this->service(callerRole: Role::Employee)->authorizeDeletion($this->callerId, $target);
    }

    // ── resolveListingScope ───────────────────────────────────────────────────

    public function testAdminWithNoFilterSeesEveryone(): void
    {
        $scope = $this->service(callerRole: Role::Admin)->resolveListingScope($this->callerId);
        $this->assertTrue($scope->isAll());
    }

    public function testAdminWithCompanyFilterSeesThoseCompanies(): void
    {
        $scope = $this->service(callerRole: Role::Admin)
            ->resolveListingScope($this->callerId, [self::COMPANY_A, self::COMPANY_B]);
        $this->assertTrue($scope->isCompanies());
        $this->assertSame([self::COMPANY_A, self::COMPANY_B], $scope->ids);
    }

    public function testCompanyAdminSeesOwnCompany(): void
    {
        $scope = $this->service(callerRole: Role::CompanyAdmin, callerCompanyId: self::COMPANY_A)
            ->resolveListingScope($this->callerId);
        $this->assertTrue($scope->isCompanies());
        $this->assertSame([self::COMPANY_A], $scope->ids);
    }

    public function testCompanyAdminCanFilterByShops(): void
    {
        $scope = $this->service(callerRole: Role::CompanyAdmin, callerCompanyId: self::COMPANY_A)
            ->resolveListingScope($this->callerId, [], [self::SHOP_A1]);
        $this->assertTrue($scope->isShops());
        $this->assertSame([self::SHOP_A1], $scope->ids);
        $this->assertSame(self::COMPANY_A, $scope->scopeCompanyId);
    }

    public function testShopManagerSeesOwnShop(): void
    {
        $scope = $this->service(callerRole: Role::ShopManager, callerShopId: self::SHOP_A1)
            ->resolveListingScope($this->callerId);
        $this->assertTrue($scope->isShops());
        $this->assertSame([self::SHOP_A1], $scope->ids);
    }

    public function testEmployeeCannotListUsers(): void
    {
        $this->expectException(ForbiddenException::class);
        $this->service(callerRole: Role::Employee)->resolveListingScope($this->callerId);
    }

    public function testAuthorizeAdminOnlyAction(): void
    {
        $this->expectException(ForbiddenException::class);
        $this->service(callerRole: Role::Employee)->authorizeAdminOnlyAction($this->callerId);
    }

    public function testAuthorizeCompanyAccessForAdmin(): void
    {
        $this->expectNotToPerformAssertions();
        $this->service(callerRole: Role::Admin)->authorizeCompanyAccess($this->callerId, self::COMPANY_A);
    }

    public function testAuthorizeCompanyAccessForCompanyAdmin(): void
    {
        $this->expectNotToPerformAssertions();
        $this->service(callerRole: Role::CompanyAdmin, callerCompanyId: self::COMPANY_A)->authorizeCompanyAccess($this->callerId, self::COMPANY_A);
    }

    public function testAuthorizeCompanyAccessForWrongCompanyAdmin(): void
    {
        $this->expectException(ForbiddenException::class);
        $this->service(callerRole: Role::CompanyAdmin, callerCompanyId: self::COMPANY_B)->authorizeCompanyAccess($this->callerId, self::COMPANY_A);
    }

    public function testAuthorizeShopAccessForAdmin(): void
    {
        $this->expectNotToPerformAssertions();
        $this->service(callerRole: Role::Admin)->authorizeShopAccess($this->callerId, self::SHOP_A1,self::COMPANY_A);
    }

    public function testAuthorizeShopAccessForCompanyAdmin(): void
    {
        $this->expectNotToPerformAssertions();
        $this->service(callerRole: Role::CompanyAdmin, callerCompanyId: self::COMPANY_A)->authorizeShopAccess($this->callerId, self::SHOP_A1,self::COMPANY_A);
    }

    public function testAuthorizeShopAccessForShopManager(): void
    {
        $this->expectNotToPerformAssertions();
        $this->service(callerRole: Role::ShopManager, callerShopId: self::SHOP_A1)->authorizeShopAccess($this->callerId, self::SHOP_A1,self::COMPANY_A);
    }

    public function testAuthorizeShopAccessForWrongShopManager(): void
    {
        $this->expectException(ForbiddenException::class);
        $this->service(callerRole: Role::ShopManager, callerShopId: self::SHOP_B1)->authorizeShopAccess($this->callerId, self::SHOP_A1,self::COMPANY_A);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function service(
        Role $callerRole = Role::Employee,
        ?string $callerCompanyId = null,
        ?string $callerShopId = null,
        bool $isUserActive = true,
    ): UserAuthorizationService {
        $caller = $this->makeUser($this->callerId, $callerRole, $callerCompanyId, $callerShopId, $isUserActive);

        $repo = $this->createStub(UserRepositoryInterface::class);
        $repo->method('findById')->willReturnCallback(
            static fn(UserId $id): ?User => $caller->id->equals($id) ? $caller : null,
        );

        return new UserAuthorizationService($repo);
    }

    private function makeUser(
        UserId $id,
        Role $role = Role::Employee,
        ?string $companyId = null,
        ?string $shopId = null,
        bool $isActive = true,
    ): User {
        return new User(
            id:        $id,
            email:     new Email($id->value . '@test.test'),
            password:  HashedPassword::fromPlain(new PlainPassword('password123')),
            firstName: new FirstName('Test'),
            lastName:  new LastName('User'),
            role:      $role,
            companyId: $companyId,
            shopId:    $shopId,
            isActive:  $isActive,
        );
    }
}
