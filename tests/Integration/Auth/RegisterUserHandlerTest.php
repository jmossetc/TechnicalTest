<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Integration\Auth;

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

    private function cmd(string $email = 'alice@example.com', string $password = 'password123'): RegisterUser
    {
        return new RegisterUser($email, $password, 'Alice', 'Smith');
    }

    public function testRegistersNewUser(): void
    {
        $userId = $this->handler->handle($this->cmd());

        $saved = $this->repository->findById($userId);

        $this->assertNotNull($saved);
        $this->assertSame('alice@example.com', $saved->email->value);
        $this->assertSame('Alice', $saved->firstName->value);
        $this->assertSame('Smith', $saved->lastName->value);
        $this->assertSame(Role::Employee, $saved->role);
    }

    public function testReturnedUserIdMatchesSavedUser(): void
    {
        $userId = $this->handler->handle($this->cmd());
        $saved  = $this->repository->findById($userId);

        $this->assertNotNull($saved);
        $this->assertTrue($userId->equals($saved->id));
    }

    public function testHashesPassword(): void
    {
        $this->handler->handle($this->cmd());

        $user = $this->repository->findByEmail(new Email('alice@example.com'));

        $this->assertNotNull($user);
        $this->assertNotSame('password123', $user->password->hash);
    }

    public function testRoleIsStoredOnUser(): void
    {
        $this->handler->handle(new RegisterUser(
            'cm@example.com', 'password123', 'Carol', 'M',
            'company_admin', '11111111-1111-4111-8111-111111111111',
        ));

        $user = $this->repository->findByEmail(new Email('cm@example.com'));

        $this->assertNotNull($user);
        $this->assertSame(Role::CompanyAdmin, $user->role);
        $this->assertSame('11111111-1111-4111-8111-111111111111', $user->companyId);
    }

    public function testThrowsWhenEmailAlreadyRegistered(): void
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

    public function testThrowsOnTooShortPassword(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->handler->handle(new RegisterUser('alice@example.com', 'short', 'A', 'B'));
    }

    public function testEmailIsCaseInsensitive(): void
    {
        $this->handler->handle($this->cmd());

        $this->expectException(UserAlreadyExistsException::class);
        $this->handler->handle(new RegisterUser('ALICE@EXAMPLE.COM', 'password123', 'A', 'B'));
    }
}
