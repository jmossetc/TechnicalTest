<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Auth\Application\Handler;

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
    private InMemoryUserRepository $repository;
    private ListUsersHandler $handler;
    private RegisterUserHandler $registrar;

    protected function setUp(): void
    {
        $this->repository = new InMemoryUserRepository();
        $this->handler    = new ListUsersHandler($this->repository);
        $this->registrar  = new RegisterUserHandler($this->repository, new PasswordHasher(''));
    }

    public function testReturnsEmptyResultWhenNoUsers(): void
    {
        $result = $this->handler->handle(new ListUsers());

        $this->assertSame([], $result->users);
        $this->assertSame(0, $result->total);
    }

    public function testReturnsAllUsersForAdminScope(): void
    {
        $this->register('a@example.com');
        $this->register('b@example.com');

        $result = $this->handler->handle(new ListUsers(limit: 10));

        $this->assertCount(2, $result->users);
        $this->assertSame(2, $result->total);
    }

    public function testPaginationLimitIsRespected(): void
    {
        $this->register('a@example.com');
        $this->register('b@example.com');
        $this->register('c@example.com');

        $result = $this->handler->handle(new ListUsers(page: 1, limit: 2));

        $this->assertCount(2, $result->users);
        $this->assertSame(3, $result->total);
        $this->assertSame(2, $result->pages());
    }

    public function testSecondPageReturnsRemainder(): void
    {
        $this->register('a@example.com');
        $this->register('b@example.com');
        $this->register('c@example.com');

        $result = $this->handler->handle(new ListUsers(page: 2, limit: 2));

        $this->assertCount(1, $result->users);
        $this->assertSame('c@example.com', $result->users[0]->email->value);
    }

    public function testOrderedByEmailAscending(): void
    {
        $this->register('charlie@example.com');
        $this->register('alice@example.com');
        $this->register('bob@example.com');

        $result  = $this->handler->handle(new ListUsers(limit: 10));
        $emails  = array_map(fn($u) => $u->email->value, $result->users);

        $this->assertSame(['alice@example.com', 'bob@example.com', 'charlie@example.com'], $emails);
    }

    public function testCompanyScopeFiltersCorrectly(): void
    {
        $company = '11111111-1111-4111-8111-111111111111';
        $this->register('cm@example.com', 'company_admin', $company);
        $this->register('other@example.com');

        $result = $this->handler->handle(new ListUsers(scope: UserScope::companies([$company])));

        $this->assertCount(1, $result->users);
        $this->assertSame('cm@example.com', $result->users[0]->email->value);
    }

    public function testShopScopeFiltersCorrectly(): void
    {
        $shop    = 'aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
        $company = '11111111-1111-4111-8111-111111111111';
        $this->register('sm@example.com', 'shop_manager', $company, $shop);
        $this->register('other@example.com');

        $result = $this->handler->handle(new ListUsers(scope: UserScope::shops([$shop])));

        $this->assertCount(1, $result->users);
        $this->assertSame('sm@example.com', $result->users[0]->email->value);
    }

    public function testShopScopeWithCompanyConstraintFiltersCorrectly(): void
    {
        $shop     = 'aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
        $companyA = '11111111-1111-4111-8111-111111111111';
        $companyB = '22222222-2222-4222-8222-222222222222';
        $this->register('sm-a@example.com', 'shop_manager', $companyA, $shop);
        $this->register('sm-b@example.com', 'shop_manager', $companyB, $shop);

        // scopeCompanyId constrains to companyA only
        $result = $this->handler->handle(new ListUsers(scope: UserScope::shops([$shop], $companyA)));

        $this->assertCount(1, $result->users);
        $this->assertSame('sm-a@example.com', $result->users[0]->email->value);
    }

    public function testPageBeyondTotalReturnsEmpty(): void
    {
        $this->register('a@example.com');

        $result = $this->handler->handle(new ListUsers(page: 99));

        $this->assertSame([], $result->users);
        $this->assertSame(1, $result->total);
    }

    private function register(
        string $email,
        string $role = 'employee',
        ?string $companyId = null,
        ?string $shopId = null,
    ): void {
        $this->registrar->handle(
            new RegisterUser($email, 'password123', 'Test', 'User', $role, $companyId, $shopId),
        );
    }
}
