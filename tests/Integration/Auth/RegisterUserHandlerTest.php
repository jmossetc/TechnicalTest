<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Integration\Auth;

use InvalidArgumentException;
use Mossetc\TechnicalTest\Auth\Application\Command\RegisterUser;
use Mossetc\TechnicalTest\Auth\Application\Handler\RegisterUserHandler;
use Mossetc\TechnicalTest\Auth\Domain\Model\Email;
use Mossetc\TechnicalTest\Auth\Domain\Exception\UserAlreadyExistsException;
use Mossetc\TechnicalTest\Auth\Infrastructure\Security\PasswordHasher;
use Mossetc\TechnicalTest\Tests\Support\InMemoryUserRepository;
use Mossetc\TechnicalTest\Tests\Support\InMemoryUserRoleRepository;
use PHPUnit\Framework\TestCase;

final class RegisterUserHandlerTest extends TestCase
{
    private InMemoryUserRepository $repository;
    private RegisterUserHandler $handler;

    protected function setUp(): void
    {
        $this->repository = new InMemoryUserRepository();
        $roleRepository   = new InMemoryUserRoleRepository();
        $this->handler    = new RegisterUserHandler($this->repository, $roleRepository, new PasswordHasher(''));
    }

    public function testRegistersNewUser(): void
    {
        $userId = $this->handler->handle(new RegisterUser('alice@example.com', 'password123'));

        $saved = $this->repository->findById($userId);

        $this->assertNotNull($saved);
        $this->assertSame('alice@example.com', $saved->email->value);
    }

    public function testReturnedUserIdMatchesSavedUser(): void
    {
        $userId = $this->handler->handle(new RegisterUser('alice@example.com', 'password123'));

        $saved = $this->repository->findById($userId);

        $this->assertNotNull($saved);
        $this->assertTrue($userId->equals($saved->id));
    }

    public function testHashesPassword(): void
    {
        $this->handler->handle(new RegisterUser('alice@example.com', 'password123'));

        $user = $this->repository->findByEmail(new Email('alice@example.com'));

        $this->assertNotNull($user);
        $this->assertNotSame('password123', $user->password->hash);
    }

    public function testThrowsWhenEmailAlreadyRegistered(): void
    {
        $command = new RegisterUser('alice@example.com', 'password123');
        $this->handler->handle($command);

        $this->expectException(UserAlreadyExistsException::class);
        $this->handler->handle($command);
    }

    public function testThrowsOnInvalidEmail(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->handler->handle(new RegisterUser('not-an-email', 'password123'));
    }

    public function testThrowsOnTooShortPassword(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->handler->handle(new RegisterUser('alice@example.com', 'short'));
    }

    public function testEmailIsCaseInsensitive(): void
    {
        $this->handler->handle(new RegisterUser('alice@example.com', 'password123'));

        $this->expectException(UserAlreadyExistsException::class);
        $this->handler->handle(new RegisterUser('ALICE@EXAMPLE.COM', 'password123'));
    }
}
