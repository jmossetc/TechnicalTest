<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Auth\Application\Handler;

use InvalidArgumentException;
use Mossetc\TechnicalTest\Auth\Application\Command\RegisterUser;
use Mossetc\TechnicalTest\Auth\Application\Handler\RegisterUserHandler;
use Mossetc\TechnicalTest\Auth\Domain\Exception\UserAlreadyExistsException;
use Mossetc\TechnicalTest\Auth\Domain\Model\Email;
use Mossetc\TechnicalTest\Auth\Domain\Model\Role;
use Mossetc\TechnicalTest\Auth\Infrastructure\Security\PasswordHasher;
use Mossetc\TechnicalTest\Tests\Support\InMemoryUserRepository;
use PHPUnit\Framework\TestCase;

final class RegisterUserHandlerTest extends TestCase
{
    private InMemoryUserRepository $repository;
    private RegisterUserHandler $handler;

    protected function setUp(): void
    {
        $this->repository = new InMemoryUserRepository();
        $this->handler    = new RegisterUserHandler($this->repository, new PasswordHasher(''));
    }

    private function cmd(string $email = 'alice@example.com', string $role = 'employee'): RegisterUser
    {
        return new RegisterUser($email, 'password123', 'Alice', 'Smith', $role);
    }

    public function testRegistersNewUser(): void
    {
        $userId = $this->handler->handle($this->cmd());

        $saved = $this->repository->findById($userId);
        self::assertNotNull($saved);
        self::assertSame('alice@example.com', $saved->email->value);
    }

    public function testReturnsGeneratedId(): void
    {
        $id   = $this->handler->handle($this->cmd());
        $user = $this->repository->findById($id);

        self::assertNotNull($user);
        self::assertTrue($id->equals($user->id));
    }

    public function testHashesPassword(): void
    {
        $this->handler->handle($this->cmd());

        $user = $this->repository->findByEmail(new Email('alice@example.com'));
        self::assertNotNull($user);
        self::assertNotSame('password123', $user->password->hash);
    }

    public function testStoresFirstAndLastName(): void
    {
        $this->handler->handle(new RegisterUser('a@b.com', 'password123', 'Bob', 'Jones'));

        $user = $this->repository->findByEmail(new Email('a@b.com'));
        self::assertNotNull($user);
        self::assertSame('Bob', $user->firstName->value);
        self::assertSame('Jones', $user->lastName->value);
    }

    public function testStoresRole(): void
    {
        $this->handler->handle(new RegisterUser(
            'cm@example.com',
            'password123',
            'Carol',
            'M',
            'company_admin',
            '11111111-1111-4111-8111-111111111111',
        ));

        $user = $this->repository->findByEmail(new Email('cm@example.com'));
        self::assertNotNull($user);
        self::assertSame(Role::CompanyAdmin, $user->role);
        self::assertSame('11111111-1111-4111-8111-111111111111', $user->companyId);
    }

    public function testDefaultRoleIsEmployee(): void
    {
        $this->handler->handle($this->cmd());

        $user = $this->repository->findByEmail(new Email('alice@example.com'));
        self::assertNotNull($user);
        self::assertSame(Role::Employee, $user->role);
    }

    public function testStoresShopId(): void
    {
        $this->handler->handle(new RegisterUser(
            'sm@example.com',
            'password123',
            'Sam',
            'M',
            'shop_manager',
            '11111111-1111-4111-8111-111111111111',
            'aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
        ));

        $user = $this->repository->findByEmail(new Email('sm@example.com'));
        self::assertNotNull($user);
        self::assertSame('aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa', $user->shopId);
    }

    public function testThrowsOnDuplicateEmail(): void
    {
        $this->handler->handle($this->cmd());

        $this->expectException(UserAlreadyExistsException::class);
        $this->handler->handle($this->cmd());
    }

    public function testThrowsOnInvalidEmail(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->handler->handle(new RegisterUser('not-an-email', 'password123', 'A', 'B'));
    }

    public function testThrowsOnShortPassword(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->handler->handle(new RegisterUser('a@b.com', 'short', 'A', 'B'));
    }

    public function testEmailIsCaseInsensitive(): void
    {
        $this->handler->handle($this->cmd('alice@example.com'));

        $this->expectException(UserAlreadyExistsException::class);
        $this->handler->handle($this->cmd('ALICE@EXAMPLE.COM'));
    }
}
