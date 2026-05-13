<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Auth\Presentation\Controller;

use Mossetc\TechnicalTest\Auth\Application\Handler\RegisterUserHandler;
use Mossetc\TechnicalTest\Auth\Domain\Model\Email;
use Mossetc\TechnicalTest\Auth\Domain\Model\FirstName;
use Mossetc\TechnicalTest\Auth\Domain\Model\HashedPassword;
use Mossetc\TechnicalTest\Auth\Domain\Model\LastName;
use Mossetc\TechnicalTest\Auth\Domain\Model\PlainPassword;
use Mossetc\TechnicalTest\Auth\Domain\Model\Role;
use Mossetc\TechnicalTest\Auth\Domain\Model\User;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserId;
use Mossetc\TechnicalTest\Auth\Domain\Service\TokenServiceInterface;
use Mossetc\TechnicalTest\Auth\Domain\Service\UserAuthorization;
use Mossetc\TechnicalTest\Auth\Infrastructure\Jwt\JwtAuthMiddleware;
use Mossetc\TechnicalTest\Auth\Infrastructure\Security\PasswordHasher;
use Mossetc\TechnicalTest\Auth\Presentation\Controller\RegisterUserController;
use Mossetc\TechnicalTest\Company\Domain\Model\CompanyId;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Request;
use Mossetc\TechnicalTest\Shop\Domain\Model\Shop;
use Mossetc\TechnicalTest\Shop\Domain\Model\ShopAddress;
use Mossetc\TechnicalTest\Shop\Domain\Model\ShopId;
use Mossetc\TechnicalTest\Shop\Domain\Model\ShopName;
use Mossetc\TechnicalTest\Shop\Domain\Repository\ShopRepositoryInterface;
use Mossetc\TechnicalTest\Tests\Support\InMemoryUserRepository;
use PHPUnit\Framework\TestCase;

final class RegisterUserControllerTest extends TestCase
{
    private UserId $callerId;
    private InMemoryUserRepository $userRepo;

    protected function setUp(): void
    {
        $this->callerId = UserId::generate();
        $this->userRepo = new InMemoryUserRepository();
    }

    private function makeAuth(): JwtAuthMiddleware
    {
        $svc = $this->createStub(TokenServiceInterface::class);
        $svc->method('validate')->willReturn($this->callerId);

        return new JwtAuthMiddleware($svc);
    }

    private function seedCaller(Role $role, ?string $companyId = null): void
    {
        $this->userRepo->save(new User(
            id:        $this->callerId,
            email:     new Email('caller@test.test'),
            password:  HashedPassword::fromPlain(new PlainPassword('password123')),
            firstName: new FirstName('Caller'),
            lastName:  new LastName('User'),
            role:      $role,
            companyId: $companyId,
        ));
    }

    private function makeShopRepo(?string $companyId = null): ShopRepositoryInterface
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

    private function ctrl(?ShopRepositoryInterface $shopRepo = null): RegisterUserController
    {
        return new RegisterUserController(
            new RegisterUserHandler($this->userRepo, new PasswordHasher('')),
            $this->makeAuth(),
            new UserAuthorization($this->userRepo),
            $shopRepo ?? $this->createStub(ShopRepositoryInterface::class),
        );
    }

    private function req(array $body): Request
    {
        return new Request('POST', '/api/users', ['Authorization' => 'Bearer tok'], $body);
    }

    private function validBody(array $overrides = []): array
    {
        return array_merge([
            'email'      => 'new@example.com',
            'password'   => 'password123',
            'first_name' => 'New',
            'last_name'  => 'User',
        ], $overrides);
    }

    public function testReturns201OnSuccess(): void
    {
        $this->seedCaller(Role::Admin);

        $response = $this->ctrl()($this->req($this->validBody()));

        $this->assertSame(201, $response->status());
        $this->assertArrayHasKey('id', $response->data());
    }

    public function testReturns401WhenNoAuthorizationHeader(): void
    {
        $response = $this->ctrl()(new Request('POST', '/api/users', [], $this->validBody()));

        $this->assertSame(401, $response->status());
    }

    public function testReturns422WhenEmailMissing(): void
    {
        $this->seedCaller(Role::Admin);

        $response = $this->ctrl()($this->req($this->validBody(['email' => ''])));

        $this->assertSame(422, $response->status());
    }

    public function testReturns422WhenPasswordMissing(): void
    {
        $this->seedCaller(Role::Admin);

        $response = $this->ctrl()($this->req($this->validBody(['password' => ''])));

        $this->assertSame(422, $response->status());
    }

    public function testReturns422WhenFirstNameMissing(): void
    {
        $this->seedCaller(Role::Admin);

        $response = $this->ctrl()($this->req($this->validBody(['first_name' => ''])));

        $this->assertSame(422, $response->status());
    }

    public function testReturns422WhenLastNameMissing(): void
    {
        $this->seedCaller(Role::Admin);

        $response = $this->ctrl()($this->req($this->validBody(['last_name' => ''])));

        $this->assertSame(422, $response->status());
    }

    public function testReturns422ForUnknownRole(): void
    {
        $this->seedCaller(Role::Admin);

        $response = $this->ctrl()($this->req($this->validBody(['role' => 'superuser'])));

        $this->assertSame(422, $response->status());
    }

    public function testReturns422ForCompanyAdminWithoutCompanyId(): void
    {
        $this->seedCaller(Role::Admin);

        $response = $this->ctrl()($this->req($this->validBody(['role' => 'company_admin'])));

        $this->assertSame(422, $response->status());
    }

    public function testReturns422ForShopManagerWithoutShopId(): void
    {
        $this->seedCaller(Role::Admin);

        $response = $this->ctrl()($this->req($this->validBody(['role' => 'shop_manager'])));

        $this->assertSame(422, $response->status());
    }

    public function testReturns403WhenForbidden(): void
    {
        $this->seedCaller(Role::Employee);

        $response = $this->ctrl()($this->req($this->validBody()));

        $this->assertSame(403, $response->status());
    }

    public function testReturns409OnDuplicateEmail(): void
    {
        $this->seedCaller(Role::Admin);
        // Pre-register the same email
        $this->userRepo->save(new User(
            id: UserId::generate(), email: new Email('new@example.com'),
            password: HashedPassword::fromPlain(new PlainPassword('password123')),
            firstName: new FirstName('X'), lastName: new LastName('Y'), role: Role::Employee,
        ));

        $response = $this->ctrl()($this->req($this->validBody()));

        $this->assertSame(409, $response->status());
    }

    public function testDefaultRoleIsEmployee(): void
    {
        $this->seedCaller(Role::Admin);
        $this->ctrl()($this->req($this->validBody()));

        $user = $this->userRepo->findByEmail(new Email('new@example.com'));
        $this->assertNotNull($user);
        $this->assertSame(Role::Employee, $user->role);
    }

    public function testCompanyAdminCanRegisterEmployeeForOwnCompany(): void
    {
        $companyId = '11111111-1111-4111-8111-111111111111';
        $this->seedCaller(Role::CompanyAdmin, $companyId);

        $response = $this->ctrl()($this->req($this->validBody()));

        $this->assertSame(201, $response->status());
    }

    public function testShopManagerResolvesCompanyFromShop(): void
    {
        $companyId = '11111111-1111-4111-8111-111111111111';
        $shopId    = ShopId::generate()->value;
        $this->seedCaller(Role::Admin);

        $this->ctrl($this->makeShopRepo($companyId))(
            $this->req($this->validBody(['role' => 'shop_manager', 'shop_id' => $shopId])),
        );

        $user = $this->userRepo->findByEmail(new Email('new@example.com'));
        $this->assertNotNull($user);
        $this->assertSame(Role::ShopManager, $user->role);
        $this->assertSame($companyId, $user->companyId);
    }
}
