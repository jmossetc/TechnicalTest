<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Auth\Presentation\Controller;

use Mossetc\TechnicalTest\Auth\Application\Handler\RegisterUserHandler;
use Mossetc\TechnicalTest\Auth\Domain\Exception\InvalidTokenException;
use Mossetc\TechnicalTest\Auth\Domain\Model\Email;
use Mossetc\TechnicalTest\Auth\Domain\Model\FirstName;
use Mossetc\TechnicalTest\Auth\Domain\Model\HashedPassword;
use Mossetc\TechnicalTest\Auth\Domain\Model\LastName;
use Mossetc\TechnicalTest\Auth\Domain\Model\PlainPassword;
use Mossetc\TechnicalTest\Auth\Domain\Model\Role;
use Mossetc\TechnicalTest\Auth\Domain\Model\User;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserId;
use Mossetc\TechnicalTest\Auth\Domain\Service\RegistrationInputValidatorService;
use Mossetc\TechnicalTest\Auth\Domain\Service\TokenServiceInterface;
use Mossetc\TechnicalTest\Auth\Domain\Service\UserAuthorizationService;
use Mossetc\TechnicalTest\Auth\Infrastructure\Jwt\JwtAuthMiddleware;
use Mossetc\TechnicalTest\Auth\Infrastructure\Security\PasswordHasher;
use Mossetc\TechnicalTest\Auth\Presentation\Controller\RegisterUserController;
use Mossetc\TechnicalTest\Company\Domain\Model\Company;
use Mossetc\TechnicalTest\Company\Domain\Model\CompanyId;
use Mossetc\TechnicalTest\Company\Domain\Model\CompanyName;
use Mossetc\TechnicalTest\Company\Domain\Repository\CompanyRepositoryInterface;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Request;
use Mossetc\TechnicalTest\Shop\Domain\Model\Shop;
use Mossetc\TechnicalTest\Shop\Domain\Model\ShopAddress;
use Mossetc\TechnicalTest\Shop\Domain\Model\ShopId;
use Mossetc\TechnicalTest\Shop\Domain\Model\ShopName;
use Mossetc\TechnicalTest\Shop\Domain\Repository\ShopRepositoryInterface;
use Mossetc\TechnicalTest\Tests\Support\InMemoryUserRepository;
use libphonenumber\PhoneNumberUtil;
use PHPUnit\Framework\TestCase;

final class RegisterUserControllerTest extends TestCase
{
    private const string COMPANY_A = '11111111-1111-4111-8111-111111111111';
    private const string SHOP_A    = 'aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa';

    private UserId $callerId;
    private InMemoryUserRepository $userRepo;

    protected function setUp(): void
    {
        $this->callerId = UserId::generate();
        $this->userRepo = new InMemoryUserRepository();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function makeAuth(): JwtAuthMiddleware
    {
        $svc = self::createStub(TokenServiceInterface::class);
        $svc->method('validate')->willReturn($this->callerId);

        return new JwtAuthMiddleware($svc);
    }

    private function seedCaller(Role $role, ?string $companyId = null, ?string $shopId = null): void
    {
        $this->userRepo->save(new User(
            id: $this->callerId,
            email: new Email('caller@test.test'),
            password: HashedPassword::fromPlain(new PlainPassword('password123')),
            firstName: new FirstName('Caller'),
            lastName: new LastName('User'),
            role: $role,
            companyId: $companyId,
            shopId: $shopId,
        ));
    }

    private function makeShopRepo(?string $companyId = null): ShopRepositoryInterface
    {
        $repo = self::createStub(ShopRepositoryInterface::class);

        if ($companyId !== null) {
            $shop = new Shop(
                id: ShopId::generate(),
                companyId: new CompanyId($companyId),
                name: new ShopName('Test Shop'),
                address: new ShopAddress(),
            );
            $repo->method('findById')->willReturn($shop);
        }

        return $repo;
    }

    private function makeCompanyRepo(bool $found = true): CompanyRepositoryInterface
    {
        $repo = self::createStub(CompanyRepositoryInterface::class);
        if ($found) {
            $repo->method('findById')->willReturn(new Company(
                id: CompanyId::generate(),
                name: new CompanyName('Test Company'),
            ));
        }
        return $repo;
    }

    private function ctrl(
        ?ShopRepositoryInterface $shopRepo = null,
        ?CompanyRepositoryInterface $companyRepo = null,
    ): RegisterUserController {
        return new RegisterUserController(
            new RegisterUserHandler($this->userRepo, new PasswordHasher('')),
            $this->makeAuth(),
            new UserAuthorizationService($this->userRepo),
            new RegistrationInputValidatorService(
                $shopRepo ?? self::createStub(ShopRepositoryInterface::class),
                $companyRepo ?? self::createStub(CompanyRepositoryInterface::class),
                PhoneNumberUtil::getInstance()
            ),
        );
    }

    /** @param array<string, mixed> $body */
    private function req(array $body): Request
    {
        return new Request('POST', '/api/users', ['Authorization' => 'Bearer tok'], $body);
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function validBody(array $overrides = []): array
    {
        return array_merge([
            'email'      => 'new@example.com',
            'password'   => 'password123',
            'first_name' => 'New',
            'last_name'  => 'User',
            'role'       => 'employee',
        ], $overrides);
    }

    // ── Success cases ─────────────────────────────────────────────────────────

    public function testReturns201WithIdOnSuccess(): void
    {
        $this->seedCaller(Role::Admin);

        $response = $this->ctrl($this->makeShopRepo(self::COMPANY_A))($this->req($this->validBody([
            'shop_id' => self::SHOP_A,
        ])));

        self::assertSame(201, $response->status());
        self::assertArrayHasKey('id', $response->data());
    }

    public function testCreatedUserIsPersisted(): void
    {
        $this->seedCaller(Role::Admin);

        $this->ctrl($this->makeShopRepo(self::COMPANY_A))($this->req($this->validBody([
            'shop_id' => self::SHOP_A,
        ])));

        self::assertSame(2, $this->userRepo->count()); // caller + new user
    }

    public function testReturnedIdMatchesPersistedUser(): void
    {
        $this->seedCaller(Role::Admin);

        $response = $this->ctrl($this->makeShopRepo(self::COMPANY_A))($this->req($this->validBody([
            'shop_id' => self::SHOP_A,
        ])));

        $id = $response->data()['id'] ?? null;
        self::assertIsString($id);
        self::assertNotNull($this->userRepo->findById(new UserId($id)));
    }

    public function testAdminCanCreateCompanyAdmin(): void
    {
        $this->seedCaller(Role::Admin);

        $response = $this->ctrl(companyRepo: $this->makeCompanyRepo())($this->req($this->validBody([
            'role'       => 'company_admin',
            'company_id' => self::COMPANY_A,
        ])));

        self::assertSame(201, $response->status());
    }

    public function testAdminCanCreateShopManagerWhenShopExists(): void
    {
        $this->seedCaller(Role::Admin);

        $response = $this->ctrl($this->makeShopRepo(self::COMPANY_A))($this->req($this->validBody([
            'role'    => 'shop_manager',
            'shop_id' => self::SHOP_A,
        ])));

        self::assertSame(201, $response->status());
    }

    public function testCompanyAdminCanCreateEmployeeInOwnCompany(): void
    {
        $this->seedCaller(Role::CompanyAdmin, companyId: self::COMPANY_A);

        $response = $this->ctrl($this->makeShopRepo(self::COMPANY_A))($this->req($this->validBody([
            'role'    => 'employee',
            'shop_id' => self::SHOP_A,
        ])));

        self::assertSame(201, $response->status());
    }

    public function testCompanyAdminCanCreateCompanyAdminInOwnCompany(): void
    {
        $this->seedCaller(Role::CompanyAdmin, companyId: self::COMPANY_A);

        $response = $this->ctrl(companyRepo: $this->makeCompanyRepo())($this->req($this->validBody([
            'role'       => 'company_admin',
            'company_id' => self::COMPANY_A,
        ])));

        self::assertSame(201, $response->status());
    }

    public function testShopManagerCanCreateEmployee(): void
    {
        $this->seedCaller(Role::ShopManager, shopId: self::SHOP_A);

        $response = $this->ctrl($this->makeShopRepo(self::COMPANY_A))($this->req($this->validBody([
            'role'    => 'employee',
            'shop_id' => self::SHOP_A,
        ])));

        self::assertSame(201, $response->status());
    }

    // ── Authentication errors (401) ───────────────────────────────────────────

    public function testReturns401WhenNoAuthorizationHeader(): void
    {
        $response = $this->ctrl()(new Request('POST', '/api/users', [], $this->validBody()));

        self::assertSame(401, $response->status());
    }

    public function testReturns401WhenTokenIsInvalid(): void
    {
        $svc = self::createStub(TokenServiceInterface::class);
        $svc->method('validate')->willThrowException(new InvalidTokenException('bad token'));
        $ctrl = new RegisterUserController(
            new RegisterUserHandler($this->userRepo, new PasswordHasher('')),
            new JwtAuthMiddleware($svc),
            new UserAuthorizationService($this->userRepo),
            new RegistrationInputValidatorService(
                self::createStub(ShopRepositoryInterface::class),
                self::createStub(CompanyRepositoryInterface::class),
                PhoneNumberUtil::getInstance()
            ),
        );

        $response = $ctrl($this->req($this->validBody()));

        self::assertSame(401, $response->status());
    }

    // ── Validation errors (422) ───────────────────────────────────────────────

    public function testReturns422WhenEmailIsMissing(): void
    {
        $this->seedCaller(Role::Admin);
        $body = $this->validBody();
        unset($body['email']);

        self::assertSame(422, $this->ctrl()($this->req($body))->status());
    }

    public function testReturns422WhenPasswordIsMissing(): void
    {
        $this->seedCaller(Role::Admin);
        $body = $this->validBody();
        unset($body['password']);

        self::assertSame(422, $this->ctrl()($this->req($body))->status());
    }

    public function testReturns422WhenFirstNameIsMissing(): void
    {
        $this->seedCaller(Role::Admin);
        $body = $this->validBody();
        unset($body['first_name']);

        self::assertSame(422, $this->ctrl()($this->req($body))->status());
    }

    public function testReturns422WhenLastNameIsMissing(): void
    {
        $this->seedCaller(Role::Admin);
        $body = $this->validBody();
        unset($body['last_name']);

        self::assertSame(422, $this->ctrl()($this->req($body))->status());
    }

    public function testReturns422WhenRoleIsMissing(): void
    {
        $this->seedCaller(Role::Admin);
        $body = $this->validBody();
        unset($body['role']);

        self::assertSame(422, $this->ctrl()($this->req($body))->status());
    }

    public function testReturns422WhenRoleIsInvalid(): void
    {
        $this->seedCaller(Role::Admin);

        self::assertSame(422, $this->ctrl()($this->req($this->validBody(['role' => 'superuser'])))->status());
    }

    public function testReturns422WhenCompanyAdminHasNoCompanyId(): void
    {
        $this->seedCaller(Role::Admin);

        self::assertSame(422, $this->ctrl()($this->req($this->validBody(['role' => 'company_admin'])))->status());
    }

    public function testReturns422WhenShopManagerHasNoShopId(): void
    {
        $this->seedCaller(Role::Admin);

        self::assertSame(422, $this->ctrl()($this->req($this->validBody(['role' => 'shop_manager'])))->status());
    }

    public function testReturns422WhenEmployeeHasNoShopId(): void
    {
        $this->seedCaller(Role::Admin);

        self::assertSame(422, $this->ctrl()($this->req($this->validBody(['role' => 'employee'])))->status());
    }

    public function testReturns422WhenShopManagerShopDoesNotExist(): void
    {
        $this->seedCaller(Role::Admin);
        // shop repo returns null → shop not found
        $response = $this->ctrl($this->makeShopRepo(null))($this->req($this->validBody([
            'role'    => 'shop_manager',
            'shop_id' => self::SHOP_A,
        ])));

        self::assertSame(422, $response->status());
    }

    // ── Authorization errors (403) ────────────────────────────────────────────

    public function testReturns403WhenEmployeeTriesToRegister(): void
    {
        $this->seedCaller(Role::Employee, shopId: self::SHOP_A);

        self::assertSame(403, $this->ctrl($this->makeShopRepo(self::COMPANY_A))($this->req($this->validBody([
            'shop_id' => self::SHOP_A,
        ])))->status());
    }

    public function testReturns403WhenCompanyAdminTriesToCreateAdmin(): void
    {
        $this->seedCaller(Role::CompanyAdmin, companyId: self::COMPANY_A);

        self::assertSame(403, $this->ctrl()($this->req($this->validBody(['role' => 'admin'])))->status());
    }

    public function testReturns403WhenCompanyAdminCreatesUserForOtherCompany(): void
    {
        $this->seedCaller(Role::CompanyAdmin, companyId: self::COMPANY_A);
        $otherCompany = '22222222-2222-4222-8222-222222222222';

        $response = $this->ctrl(companyRepo: $this->makeCompanyRepo())($this->req($this->validBody([
            'role'       => 'company_admin',
            'company_id' => $otherCompany,
        ])));

        self::assertSame(403, $response->status());
    }

    // ── Conflict (409) ────────────────────────────────────────────────────────

    public function testReturns409WhenEmailIsAlreadyTaken(): void
    {
        $this->seedCaller(Role::Admin);
        $this->userRepo->save(new User(
            id: UserId::generate(),
            email: new Email('new@example.com'),
            password: HashedPassword::fromPlain(new PlainPassword('password123')),
            firstName: new FirstName('Existing'),
            lastName: new LastName('User'),
            role: Role::Employee,
            shopId: self::SHOP_A,
        ));

        self::assertSame(409, $this->ctrl($this->makeShopRepo(self::COMPANY_A))($this->req($this->validBody([
            'shop_id' => self::SHOP_A,
        ])))->status());
    }
}
