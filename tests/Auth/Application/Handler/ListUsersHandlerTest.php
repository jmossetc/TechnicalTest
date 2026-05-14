<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Auth\Application\Handler;

use Mossetc\TechnicalTest\Auth\Application\Command\ListUsers;
use Mossetc\TechnicalTest\Auth\Application\Command\RegisterUser;
use Mossetc\TechnicalTest\Auth\Application\Handler\ListUsersHandler;
use Mossetc\TechnicalTest\Auth\Application\Handler\RegisterUserHandler;
use Mossetc\TechnicalTest\Auth\Domain\Model\Email;
use Mossetc\TechnicalTest\Auth\Domain\Model\FirstName;
use Mossetc\TechnicalTest\Auth\Domain\Model\HashedPassword;
use Mossetc\TechnicalTest\Auth\Domain\Model\LastName;
use Mossetc\TechnicalTest\Auth\Domain\Model\PlainPassword;
use Mossetc\TechnicalTest\Auth\Domain\Model\User;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserId;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserScope;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserSearchCriteria;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserSortCriteria;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserSortField;
use Mossetc\TechnicalTest\Auth\Infrastructure\Security\PasswordHasher;
use Mossetc\TechnicalTest\Shared\Domain\SortDirection;
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

    public function testFiltersUsersByEmailPartialMatch(): void
    {
        $this->register('alice@example.com');
        $this->register('bob@example.com');

        $criteria = new UserSearchCriteria(email: 'alice');
        $result   = $this->handler->handle(new ListUsers(criteria: $criteria));

        $this->assertCount(1, $result->users);
        $this->assertSame('alice@example.com', $result->users[0]->email->value);
    }

    public function testFiltersUsersByRole(): void
    {
        $this->register('admin@example.com', 'admin');
        $this->register('emp@example.com', 'employee');

        $criteria = new UserSearchCriteria(role: \Mossetc\TechnicalTest\Auth\Domain\Model\Role::Employee);
        $result   = $this->handler->handle(new ListUsers(criteria: $criteria));

        $this->assertCount(1, $result->users);
        $this->assertSame('emp@example.com', $result->users[0]->email->value);
    }

    public function testFiltersUsersByIsActive(): void
    {
        $this->register('active@example.com');
        $inactive = new User(
            id:        UserId::generate(),
            email:     new Email('inactive@example.com'),
            password:  HashedPassword::fromPlain(new PlainPassword('password123')),
            firstName: new FirstName('In'),
            lastName:  new LastName('Active'),
            isActive:  false,
        );
        $this->repository->save($inactive);

        $criteria = new UserSearchCriteria(isActive: false);
        $result   = $this->handler->handle(new ListUsers(criteria: $criteria));

        $this->assertCount(1, $result->users);
        $this->assertSame('inactive@example.com', $result->users[0]->email->value);
    }

    public function testCombinesEmailAndRoleCriteria(): void
    {
        $this->register('alice@example.com', 'employee');
        $this->register('alice-admin@example.com', 'admin');
        $this->register('bob@example.com', 'employee');

        $criteria = new UserSearchCriteria(
            email: 'alice',
            role:  \Mossetc\TechnicalTest\Auth\Domain\Model\Role::Employee,
        );
        $result = $this->handler->handle(new ListUsers(criteria: $criteria));

        $this->assertCount(1, $result->users);
        $this->assertSame('alice@example.com', $result->users[0]->email->value);
    }

    public function testCriteriaAndScopeAreBothApplied(): void
    {
        $company = '11111111-1111-4111-8111-111111111111';
        $this->register('alice@example.com', 'employee', $company);
        $this->register('bob@example.com', 'employee');
        $this->register('admin@example.com', 'admin', $company);

        $criteria = new UserSearchCriteria(email: 'alice');
        $result   = $this->handler->handle(
            new ListUsers(scope: UserScope::companies([$company]), criteria: $criteria)
        );

        $this->assertCount(1, $result->users);
        $this->assertSame('alice@example.com', $result->users[0]->email->value);
    }

    public function testSortByEmailDescendingReturnsReverseOrder(): void
    {
        $this->register('charlie@example.com');
        $this->register('alice@example.com');
        $this->register('bob@example.com');

        $result = $this->handler->handle(new ListUsers(
            limit: 10,
            sort:  new UserSortCriteria(
                field:     UserSortField::Email,
                direction: SortDirection::Desc,
            ),
        ));

        $emails = array_map(fn($u) => $u->email->value, $result->users);
        $this->assertSame(['charlie@example.com', 'bob@example.com', 'alice@example.com'], $emails);
    }

    public function testSortByFirstNameAscendingReturnsAlphabeticalFirstNameOrder(): void
    {
        $this->register('c@example.com', firstName: 'Charlie');
        $this->register('a@example.com', firstName: 'Alice');
        $this->register('b@example.com', firstName: 'Bob');

        $result = $this->handler->handle(new ListUsers(
            limit: 10,
            sort:  new UserSortCriteria(
                field:     UserSortField::FirstName,
                direction: SortDirection::Asc,
            ),
        ));

        $names = array_map(fn($u) => $u->firstName->value, $result->users);
        $this->assertSame(['Alice', 'Bob', 'Charlie'], $names);
    }

    private function register(
        string $email,
        string $role = 'employee',
        ?string $companyId = null,
        ?string $shopId = null,
        string $firstName = 'Test',
        string $lastName  = 'User',
    ): void {
        $this->registrar->handle(
            new RegisterUser($email, 'password123', $firstName, $lastName, $role, $companyId, $shopId),
        );
    }
}
