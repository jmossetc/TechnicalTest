<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Integration\Auth;

use Mossetc\TechnicalTest\Auth\Application\Handler\ListUsersHandler;
use Mossetc\TechnicalTest\Auth\Application\Handler\RegisterUserHandler;
use Mossetc\TechnicalTest\Auth\Application\Query\ListUsers;
use Mossetc\TechnicalTest\Auth\Application\Query\RegisterUser;
use Mossetc\TechnicalTest\Tests\Support\InMemoryUserRepository;
use Mossetc\TechnicalTest\Tests\Support\InMemoryUserRoleRepository;
use PHPUnit\Framework\TestCase;

final class ListUsersHandlerTest extends TestCase
{
    private ListUsersHandler $handler;
    private RegisterUserHandler $registrar;

    protected function setUp(): void
    {
        $repo            = new InMemoryUserRepository();
        $roleRepo        = new InMemoryUserRoleRepository();
        $this->handler   = new ListUsersHandler($repo);
        $this->registrar = new RegisterUserHandler($repo, $roleRepo);
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
        $this->assertSame(1, $result->pages());
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
        $this->assertSame(3, $result->total);
        $this->assertSame('charlie@example.com', $result->users[0]->email->value);
    }

    public function testResultsAreOrderedByEmailAscending(): void
    {
        $this->register('charlie@example.com');
        $this->register('alice@example.com');
        $this->register('bob@example.com');

        $result = $this->handler->handle(new ListUsers(page: 1, limit: 10));

        $emails = array_map(static fn($u) => $u->email->value, $result->users);
        $this->assertSame(
            ['alice@example.com', 'bob@example.com', 'charlie@example.com'],
            $emails,
        );
    }

    public function testPageBeyondTotalReturnsEmptyUsers(): void
    {
        $this->register('alice@example.com');

        $result = $this->handler->handle(new ListUsers(page: 99, limit: 10));

        $this->assertSame([], $result->users);
        $this->assertSame(1, $result->total);
    }

    private function register(string $email): void
    {
        $this->registrar->handle(new RegisterUser($email, 'password123'));
    }
}
