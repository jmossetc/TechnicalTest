<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Auth\Domain\Service;

use InvalidArgumentException;
use Mossetc\TechnicalTest\Auth\Domain\Model\Role;
use Mossetc\TechnicalTest\Auth\Domain\Service\RegistrationInputValidatorService;
use Mossetc\TechnicalTest\Company\Domain\Model\CompanyId;
use Mossetc\TechnicalTest\Shop\Domain\Model\Shop;
use Mossetc\TechnicalTest\Shop\Domain\Model\ShopAddress;
use Mossetc\TechnicalTest\Shop\Domain\Model\ShopId;
use Mossetc\TechnicalTest\Shop\Domain\Model\ShopName;
use Mossetc\TechnicalTest\Shop\Domain\Repository\ShopRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class RegistrationInputValidatorServiceTest extends TestCase
{
    private const string COMPANY_A = '11111111-1111-4111-8111-111111111111';
    private const string SHOP_A    = 'aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa';

    private function validator(?ShopRepositoryInterface $shopRepo = null): RegistrationInputValidatorService
    {
        return new RegistrationInputValidatorService(
            $shopRepo ?? $this->createStub(ShopRepositoryInterface::class),
        );
    }

    /**
     * @param array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function baseBody(array $overrides = []): array
    {
        return array_merge([
            'email'      => 'alice@example.com',
            'password'   => 'password123',
            'first_name' => 'Alice',
            'last_name'  => 'Smith',
            'role'       => 'employee',
        ], $overrides);
    }

    private function shopRepoReturning(?string $companyId): ShopRepositoryInterface
    {
        $repo = $this->createStub(ShopRepositoryInterface::class);

        if ($companyId !== null) {
            $shop = new Shop(
                id:        ShopId::generate(),
                companyId: new CompanyId($companyId),
                name:      new ShopName('Test Shop'),
                address:   new ShopAddress(),
            );
            $repo->method('findById')->willReturn($shop);
        }

        return $repo;
    }

    // ── Required-field validation ─────────────────────────────────────────────

    public function testThrowsWhenEmailIsAbsent(): void
    {
        $body = $this->baseBody();
        unset($body['email']);

        $this->expectException(InvalidArgumentException::class);
        $this->validator()->validate($body);
    }

    public function testThrowsWhenPasswordIsAbsent(): void
    {
        $body = $this->baseBody();
        unset($body['password']);

        $this->expectException(InvalidArgumentException::class);
        $this->validator()->validate($body);
    }

    public function testThrowsWhenFirstNameIsAbsent(): void
    {
        $body = $this->baseBody();
        unset($body['first_name']);

        $this->expectException(InvalidArgumentException::class);
        $this->validator()->validate($body);
    }

    public function testThrowsWhenLastNameIsAbsent(): void
    {
        $body = $this->baseBody();
        unset($body['last_name']);

        $this->expectException(InvalidArgumentException::class);
        $this->validator()->validate($body);
    }

    // ── Role validation (checkRole) ───────────────────────────────────────────

    public function testThrowsWhenRoleIsAbsent(): void
    {
        $body = $this->baseBody();
        unset($body['role']);

        $this->expectException(InvalidArgumentException::class);
        $this->validator()->validate($body);
    }

    public function testThrowsWhenRoleIsEmptyString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator()->validate($this->baseBody(['role' => '']));
    }

    public function testThrowsWhenRoleIsUnknown(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator()->validate($this->baseBody(['role' => 'superuser']));
    }

    public function testThrowsOldRoleNameCompanyManager(): void
    {
        // 'company_manager' was renamed to 'company_admin' — must not be accepted
        $this->expectException(InvalidArgumentException::class);
        $this->validator()->validate($this->baseBody(['role' => 'company_manager']));
    }

    public function testThrowsWhenCompanyAdminHasNoCompanyId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator()->validate($this->baseBody(['role' => 'company_admin']));
    }

    public function testThrowsWhenShopManagerHasNoShopId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator()->validate($this->baseBody(['role' => 'shop_manager']));
    }

    // ── Shop resolution ───────────────────────────────────────────────────────

    public function testThrowsWhenShopIdIsMalformed(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator()->validate($this->baseBody([
            'role'    => 'shop_manager',
            'shop_id' => 'not-a-uuid',
        ]));
    }

    public function testThrowsWhenShopDoesNotExist(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator($this->shopRepoReturning(null))->validate($this->baseBody([
            'role'    => 'shop_manager',
            'shop_id' => self::SHOP_A,
        ]));
    }

    // ── Happy paths ───────────────────────────────────────────────────────────

    public function testValidEmployeeInputReturnsInput(): void
    {
        $input = $this->validator()->validate($this->baseBody());

        $this->assertSame(Role::Employee, $input->role);
        $this->assertSame('alice@example.com', $input->email);
        $this->assertSame('password123',       $input->password);
        $this->assertSame('Alice',             $input->firstName);
        $this->assertSame('Smith',             $input->lastName);
        $this->assertNull($input->companyId);
        $this->assertNull($input->shopId);
    }

    public function testValidAdminInputReturnsInput(): void
    {
        $input = $this->validator()->validate($this->baseBody(['role' => 'admin']));

        $this->assertSame(Role::Admin, $input->role);
    }

    public function testValidCompanyAdminInputSetsCompanyId(): void
    {
        $input = $this->validator()->validate($this->baseBody([
            'role'       => 'company_admin',
            'company_id' => self::COMPANY_A,
        ]));

        $this->assertSame(Role::CompanyAdmin,  $input->role);
        $this->assertSame(self::COMPANY_A,     $input->companyId);
        $this->assertNull($input->shopId);
    }

    public function testValidShopManagerResolvesCompanyFromShop(): void
    {
        $input = $this->validator($this->shopRepoReturning(self::COMPANY_A))->validate($this->baseBody([
            'role'    => 'shop_manager',
            'shop_id' => self::SHOP_A,
        ]));

        $this->assertSame(Role::ShopManager,   $input->role);
        $this->assertSame(self::SHOP_A,        $input->shopId);
        $this->assertSame(self::COMPANY_A,     $input->companyId);
    }

    public function testPhoneNumberIsStoredWhenPresent(): void
    {
        $input = $this->validator()->validate($this->baseBody(['phone_number' => '+33612345678']));

        $this->assertSame('+33612345678', $input->phoneNumber);
    }

    public function testPhoneNumberIsNullWhenAbsent(): void
    {
        $input = $this->validator()->validate($this->baseBody());

        $this->assertNull($input->phoneNumber);
    }

    // ── checkRole() direct tests ──────────────────────────────────────────────

    public function testCheckRoleReturnsCorrectRoleEnum(): void
    {
        $svc = $this->validator();
        $this->assertSame(Role::Admin,        $svc->checkRole('admin',        null, null));
        $this->assertSame(Role::Employee,     $svc->checkRole('employee',     null, null));
        $this->assertSame(Role::CompanyAdmin, $svc->checkRole('company_admin', self::COMPANY_A, null));
        $this->assertSame(Role::ShopManager,  $svc->checkRole('shop_manager', null, self::SHOP_A));
    }

    public function testCheckRoleThrowsOnNullRoleStr(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator()->checkRole(null, null, null);
    }

    public function testCheckRoleThrowsOnEmptyRoleStr(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator()->checkRole('', null, null);
    }

    public function testCheckRoleThrowsOnInvalidRoleStr(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator()->checkRole('wizard', null, null);
    }

    public function testCheckRoleThrowsCompanyAdminWithoutCompanyId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator()->checkRole('company_admin', null, null);
    }

    public function testCheckRoleThrowsShopManagerWithoutShopId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator()->checkRole('shop_manager', null, null);
    }
}
