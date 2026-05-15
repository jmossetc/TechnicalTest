<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Auth\Application\Handler;

use Mossetc\TechnicalTest\Auth\Application\Command\LoginUser;
use Mossetc\TechnicalTest\Auth\Application\Command\RegisterUser;
use Mossetc\TechnicalTest\Auth\Application\Handler\LoginUserHandler;
use Mossetc\TechnicalTest\Auth\Application\Handler\RegisterUserHandler;
use Mossetc\TechnicalTest\Auth\Domain\Exception\InvalidCredentialsException;
use Mossetc\TechnicalTest\Auth\Domain\Model\AuthToken;
use Mossetc\TechnicalTest\Auth\Domain\Model\Email;
use Mossetc\TechnicalTest\Auth\Domain\Model\FirstName;
use Mossetc\TechnicalTest\Auth\Domain\Model\LastName;
use Mossetc\TechnicalTest\Auth\Domain\Model\PlainPassword;
use Mossetc\TechnicalTest\Auth\Domain\Model\Role;
use Mossetc\TechnicalTest\Auth\Domain\Model\User;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserId;
use Mossetc\TechnicalTest\Auth\Domain\Service\TokenServiceInterface;
use Mossetc\TechnicalTest\Auth\Infrastructure\Security\PasswordHasher;
use Mossetc\TechnicalTest\Tests\Support\InMemoryUserRepository;
use PHPUnit\Framework\TestCase;

final class LoginUserHandlerTest extends TestCase
{
    private InMemoryUserRepository $repository;
    private PasswordHasher $hasher;

    protected function setUp(): void
    {
        $this->repository = new InMemoryUserRepository();
        $this->hasher     = new PasswordHasher('');

        new RegisterUserHandler($this->repository, $this->hasher)
            ->handle(new RegisterUser('alice@example.com', 'password123', 'Alice', 'Smith'));
    }

    private function makeHandler(?TokenServiceInterface $tokens = null): LoginUserHandler
    {
        $tokens ??= self::createStub(TokenServiceInterface::class);

        return new LoginUserHandler($this->repository, $tokens, $this->hasher);
    }

    public function testReturnsTokenOnValidCredentials(): void
    {
        $expected = new AuthToken('jwt.token.here');
        $tokens   = $this->createMock(TokenServiceInterface::class);
        $tokens->expects(self::once())->method('issue')->willReturn($expected);

        $token = $this->makeHandler($tokens)->handle(new LoginUser('alice@example.com', 'password123'));

        self::assertSame('jwt.token.here', $token->value);
    }

    public function testThrowsOnUnknownEmail(): void
    {
        $this->expectException(InvalidCredentialsException::class);
        $this->makeHandler()->handle(new LoginUser('ghost@example.com', 'password123'));
    }

    public function testThrowsOnWrongPassword(): void
    {
        $this->expectException(InvalidCredentialsException::class);
        $this->makeHandler()->handle(new LoginUser('alice@example.com', 'wrongpassss'));
    }

    public function testEmailLookupIsCaseInsensitive(): void
    {
        $tokens = $this->createMock(TokenServiceInterface::class);
        $tokens->expects(self::once())->method('issue')->willReturn(new AuthToken('tok'));

        $this->makeHandler($tokens)->handle(new LoginUser('ALICE@EXAMPLE.COM', 'password123'));
    }

    public function testUpdatesLastLoginOnSuccess(): void
    {
        $tokens = self::createStub(TokenServiceInterface::class);
        $tokens->method('issue')->willReturn(new AuthToken('tok'));

        $this->makeHandler($tokens)->handle(new LoginUser('alice@example.com', 'password123'));

        $user = $this->repository->findByEmail(new Email('alice@example.com'));
        self::assertNotNull($user?->lastLoginAt);
    }

    public function testThrowsForInactiveUser(): void
    {
        $userId = UserId::generate();
        $this->repository->save(new User(
            id: $userId,
            email: new Email('inactive@example.com'),
            password: $this->hasher->hash(new PlainPassword('password123')),
            firstName: new FirstName('In'),
            lastName: new LastName('Active'),
            role: Role::Employee,
            isActive: false,
        ));

        $this->expectException(InvalidCredentialsException::class);
        $this->makeHandler()->handle(new LoginUser('inactive@example.com', 'password123'));
    }
}
