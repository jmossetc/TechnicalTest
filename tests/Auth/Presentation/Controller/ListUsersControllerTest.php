<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Auth\Presentation\Controller;

use Mossetc\TechnicalTest\Auth\Application\Handler\ListUsersHandler;
use Mossetc\TechnicalTest\Auth\Domain\Model\Email;
use Mossetc\TechnicalTest\Auth\Domain\Model\FirstName;
use Mossetc\TechnicalTest\Auth\Domain\Model\HashedPassword;
use Mossetc\TechnicalTest\Auth\Domain\Model\LastName;
use Mossetc\TechnicalTest\Auth\Domain\Model\PlainPassword;
use Mossetc\TechnicalTest\Auth\Domain\Model\Role;
use Mossetc\TechnicalTest\Auth\Domain\Model\User;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserId;
use Mossetc\TechnicalTest\Auth\Domain\Service\TokenServiceInterface;
use Mossetc\TechnicalTest\Auth\Domain\Service\UserAuthorizationService;
use Mossetc\TechnicalTest\Auth\Infrastructure\Jwt\JwtAuthMiddleware;
use Mossetc\TechnicalTest\Auth\Presentation\Controller\ListUsersController;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Request;
use Mossetc\TechnicalTest\Tests\Support\InMemoryUserRepository;
use PHPUnit\Framework\TestCase;

final class ListUsersControllerTest extends TestCase
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

    private function seedCaller(Role $role): void
    {
        $this->userRepo->save(new User(
            id:        $this->callerId,
            email:     new Email('caller@test.test'),
            password:  HashedPassword::fromPlain(new PlainPassword('password123')),
            firstName: new FirstName('Caller'),
            lastName:  new LastName('User'),
            role:      $role,
        ));
    }

    private function ctrl(): ListUsersController
    {
        return new ListUsersController(
            new ListUsersHandler($this->userRepo),
            $this->makeAuth(),
            new UserAuthorizationService($this->userRepo),
        );
    }

    /** @param array<string, string> $query */
    private function request(array $query = []): Request
    {
        return new Request('GET', '/api/users', ['Authorization' => 'Bearer tok'], [], [], $query);
    }

    public function testReturnsUserListForAdmin(): void
    {
        $this->seedCaller(Role::Admin);
        $this->userRepo->save(new User(
            id: UserId::generate(), email: new Email('other@test.test'),
            password: HashedPassword::fromPlain(new PlainPassword('password123')),
            firstName: new FirstName('A'), lastName: new LastName('B'), role: Role::Employee,
        ));

        $response = $this->ctrl()($this->request());

        $this->assertSame(200, $response->status());
        $items = $response->data()['data'];
        $this->assertIsArray($items);
        $this->assertCount(2, $items);
    }

    public function testReturns401WhenNoAuthorizationHeader(): void
    {
        $this->seedCaller(Role::Admin);

        $response = $this->ctrl()(new Request('GET', '/api/users', [], []));

        $this->assertSame(401, $response->status());
    }

    public function testReturns403ForEmployee(): void
    {
        $this->seedCaller(Role::Employee);

        $response = $this->ctrl()($this->request());

        $this->assertSame(403, $response->status());
    }

    public function testResponseContainsPaginationMetadata(): void
    {
        $this->seedCaller(Role::Admin);

        $pagination = $this->ctrl()($this->request())->data()['pagination'];
        $this->assertIsArray($pagination);
        $this->assertArrayHasKey('total', $pagination);
        $this->assertArrayHasKey('page',  $pagination);
        $this->assertArrayHasKey('limit', $pagination);
        $this->assertArrayHasKey('pages', $pagination);
    }

    public function testResponseContainsExpectedUserFields(): void
    {
        $this->seedCaller(Role::Admin);

        $items = $this->ctrl()($this->request())->data()['data'];
        $this->assertIsArray($items);
        $item = $items[0];
        $this->assertIsArray($item);
        $this->assertArrayHasKey('id',         $item);
        $this->assertArrayHasKey('email',      $item);
        $this->assertArrayHasKey('first_name', $item);
        $this->assertArrayHasKey('last_name',  $item);
        $this->assertArrayHasKey('role',       $item);
    }

    public function testCompanyAdminSeesOnlyOwnCompany(): void
    {
        $companyId = '11111111-1111-4111-8111-111111111111';

        $this->userRepo->save(new User(
            id: $this->callerId, email: new Email('caller@test.test'),
            password: HashedPassword::fromPlain(new PlainPassword('password123')),
            firstName: new FirstName('CA'), lastName: new LastName('User'),
            role: Role::CompanyAdmin, companyId: $companyId,
        ));
        $this->userRepo->save(new User(
            id: UserId::generate(), email: new Email('other@test.test'),
            password: HashedPassword::fromPlain(new PlainPassword('password123')),
            firstName: new FirstName('A'), lastName: new LastName('B'), role: Role::Employee,
        ));

        $response = $this->ctrl()($this->request());

        $this->assertSame(200, $response->status());
        $items = $response->data()['data'];
        $this->assertIsArray($items);
        $this->assertCount(1, $items);
    }

    public function testFiltersUsersByEmailParam(): void
    {
        $this->seedCaller(Role::Admin);
        $this->userRepo->save(new User(
            id: UserId::generate(), email: new Email('alice@test.test'),
            password: HashedPassword::fromPlain(new PlainPassword('password123')),
            firstName: new FirstName('Alice'), lastName: new LastName('Smith'), role: Role::Employee,
        ));
        $this->userRepo->save(new User(
            id: UserId::generate(), email: new Email('bob@test.test'),
            password: HashedPassword::fromPlain(new PlainPassword('password123')),
            firstName: new FirstName('Bob'), lastName: new LastName('Jones'), role: Role::Employee,
        ));

        $response = $this->ctrl()($this->request(['email' => 'alice']));

        $this->assertSame(200, $response->status());
        $items = $response->data()['data'];
        $this->assertIsArray($items);
        $this->assertCount(1, $items);
        $this->assertSame('alice@test.test', $items[0]['email']);
    }

    public function testFiltersUsersByRoleParam(): void
    {
        $this->seedCaller(Role::Admin);
        $this->userRepo->save(new User(
            id: UserId::generate(), email: new Email('emp@test.test'),
            password: HashedPassword::fromPlain(new PlainPassword('password123')),
            firstName: new FirstName('E'), lastName: new LastName('Mp'), role: Role::Employee,
        ));

        $response = $this->ctrl()($this->request(['role' => 'employee']));

        $this->assertSame(200, $response->status());
        $items = $response->data()['data'];
        $this->assertIsArray($items);
        $this->assertCount(1, $items);
    }

    public function testReturns422ForInvalidRole(): void
    {
        $this->seedCaller(Role::Admin);

        $response = $this->ctrl()($this->request(['role' => 'superuser']));

        $this->assertSame(422, $response->status());
    }

    public function testReturns422ForInvalidIsActive(): void
    {
        $this->seedCaller(Role::Admin);

        $response = $this->ctrl()($this->request(['is_active' => 'maybe']));

        $this->assertSame(422, $response->status());
    }

    public function testReturns422ForInvalidDateFormat(): void
    {
        $this->seedCaller(Role::Admin);

        $response = $this->ctrl()($this->request(['created_from' => '01-01-2025']));

        $this->assertSame(422, $response->status());
    }

    public function testReturns422WhenCreatedFromAfterCreatedTo(): void
    {
        $this->seedCaller(Role::Admin);

        $response = $this->ctrl()($this->request([
            'created_from' => '2025-12-01',
            'created_to'   => '2025-01-01',
        ]));

        $this->assertSame(422, $response->status());
    }

    public function testFiltersUsersByIsActive(): void
    {
        $this->seedCaller(Role::Admin);
        $this->userRepo->save(new User(
            id: UserId::generate(), email: new Email('inactive@test.test'),
            password: HashedPassword::fromPlain(new PlainPassword('password123')),
            firstName: new FirstName('In'), lastName: new LastName('Active'),
            role: Role::Employee, isActive: false,
        ));

        $response = $this->ctrl()($this->request(['is_active' => 'false']));

        $this->assertSame(200, $response->status());
        $items = $response->data()['data'];
        $this->assertIsArray($items);
        $this->assertCount(1, $items);
    }
}
