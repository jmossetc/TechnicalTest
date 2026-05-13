<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Integration\Auth;

use Mossetc\TechnicalTest\Auth\Application\Command\ListUsers;
use Mossetc\TechnicalTest\Auth\Application\Command\RegisterUser;
use Mossetc\TechnicalTest\Auth\Application\Handler\ListUsersHandler;
use Mossetc\TechnicalTest\Auth\Application\Handler\RegisterUserHandler;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserScope;
use Mossetc\TechnicalTest\Auth\Infrastructure\Security\PasswordHasher;
use Mossetc\TechnicalTest\Tests\Support\InMemoryUserRepository;
use PHPUnit\Framework\TestCase;

final class ListUsersHandlerTest extends TestCase
{
    private ListUsersHandler $handler;
    private RegisterUserHandler $registrar;

    protected function setUp(): void
    {
        $repo            = new InMemoryUserRepository();
        $this->handler   = new ListUsersHandler($repo);
        $this->registrar = new RegisterUserHandler($repo, new PasswordHasher(''));
    }

    public function testReturnsEmptyPageWhenNoUsersExist(): void
    {
        $result = $this->handler->handle(new ListUsers(page: 1, limit: 10));

        $this->assertSame([], $result->users);
        $this->assertSame(0, $result->total);
        $this->assertSame(0, $result->pages());
    }

    public function testReturnsAllUsersOnFirstPage(): void
    {
        $this->register('alice@example.com');
        $this->register('bob@example.com');

        $result = $this->handler->handle(new ListUsers(page: 1, limit: 10));

        $this->assertCount(2, $result->users);
        $this->assertSame(2, $result->total);
    }

    public function testFirstPageRespectsLimit(): void
    {
        $this->register('alice@example.com');
        $this->register('bob@example.com');
        $this->register('charlie@example.com');

        $result = $this->handler->handle(new ListUsers(page: 1, limit: 2));

        $this->assertCount(2, $result->users);
        $this->assertSame(3, $result->total);
        $this->assertSame(2, $result->pages());
    }

    public function testSecondPageReturnsRemainder(): void
    {
        $this->register('alice@example.com');
        $this->register('bob@example.com');
        $this->register('charlie@example.com');

        $result = $this->handler->handle(new ListUsers(page: 2, limit: 2));

        $this->assertCount(1, $result->users);
        $this->assertSame('charlie@example.com', $result->users[0]->email->value);
    }

    public function testResultsAreOrderedByEmailAscending(): void
    {
        $this->register('charlie@example.com');
        $this->register('alice@example.com');
        $this->register('bob@example.com');

        $result = $this->handler->handle(new ListUsers(page: 1, limit: 10));
        $emails = array_map(static fn($u) => $u->email->value, $result->users);

        $this->assertSame(['alice@example.com', 'bob@example.com', 'charlie@example.com'], $emails);
    }

    public function testFilterByCompanyScope(): void
    {
        $company = '11111111-1111-4111-8111-111111111111';
        $this->register('cm@example.com', 'company_admin', $company);
        $this->register('other@example.com');

        $scope  = UserScope::companies([$company]);
        $result = $this->handler->handle(new ListUsers(page: 1, limit: 10, scope: $scope));

        $this->assertCount(1, $result->users);
        $this->assertSame('cm@example.com', $result->users[0]->email->value);
    }

    public function testFilterByShopScope(): void
    {
        $shop    = 'aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
        $company = '11111111-1111-4111-8111-111111111111';
        $this->register('sm@example.com', 'shop_manager', $company, $shop);
        $this->register('other@example.com');

        $scope  = UserScope::shops([$shop]);
        $result = $this->handler->handle(new ListUsers(page: 1, limit: 10, scope: $scope));

        $this->assertCount(1, $result->users);
        $this->assertSame('sm@example.com', $result->users[0]->email->value);
    }

    private function register(
        string $email,
        string $role = 'employee',
        ?string $companyId = null,
        ?string $shopId = null,
    ): void {
        $this->registrar->handle(new RegisterUser($email, 'password123', 'Test', 'User', $role, $companyId, $shopId));
    }
}
