<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Integration\Auth;

use Mossetc\TechnicalTest\Auth\Application\Command\LoginUser;
use Mossetc\TechnicalTest\Auth\Application\Command\RegisterUser;
use Mossetc\TechnicalTest\Auth\Application\Handler\LoginUserHandler;
use Mossetc\TechnicalTest\Auth\Application\Handler\RegisterUserHandler;
use Mossetc\TechnicalTest\Auth\Domain\Model\AuthToken;
use Mossetc\TechnicalTest\Auth\Domain\Model\Email;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserId;
use Mossetc\TechnicalTest\Auth\Domain\Exception\InvalidCredentialsException;
use Mossetc\TechnicalTest\Auth\Domain\Repository\UserRepositoryInterface;
use Mossetc\TechnicalTest\Auth\Domain\Service\TokenServiceInterface;
use Mossetc\TechnicalTest\Auth\Infrastructure\Security\PasswordHasher;
use Mossetc\TechnicalTest\Tests\Support\InMemoryUserRepository;
use Mossetc\TechnicalTest\Tests\Support\InMemoryUserRoleRepository;
use PHPUnit\Framework\TestCase;

final class LoginUserHandlerTest extends TestCase
{
    private UserRepositoryInterface $repository;
    private PasswordHasher $hasher;

    protected function setUp(): void
    {
        $this->repository = new InMemoryUserRepository();
        $this->hasher     = new PasswordHasher('');

        $roleRepo = new InMemoryUserRoleRepository();
        (new RegisterUserHandler($this->repository, $roleRepo, $this->hasher))
            ->handle(new RegisterUser('alice@example.com', 'password123'));
    }

    private function makeHandler(?TokenServiceInterface $tokenService = null): LoginUserHandler
    {
        return new LoginUserHandler(
            $this->repository,
            $tokenService ?? $this->createStub(TokenServiceInterface::class),
            $this->hasher,
        );
    }

    public function testReturnsTokenOnSuccess(): void
    {
        $expectedToken = new AuthToken('some.jwt.token');
        $tokenService  = $this->createMock(TokenServiceInterface::class);
        $tokenService->expects($this->once())->method('issue')->willReturn($expectedToken);

        $token = $this->makeHandler($tokenService)
            ->handle(new LoginUser('alice@example.com', 'password123'));

        $this->assertSame('some.jwt.token', $token->value);
    }

    public function testPassesUserIdAndEmailToTokenService(): void
    {
        $tokenService = $this->createMock(TokenServiceInterface::class);
        $tokenService
            ->expects($this->once())
            ->method('issue')
            ->with(
                $this->isInstanceOf(UserId::class),
                $this->callback(fn(Email $e) => $e->value === 'alice@example.com'),
            )
            ->willReturn(new AuthToken('tok'));

        $this->makeHandler($tokenService)
            ->handle(new LoginUser('alice@example.com', 'password123'));
    }

    public function testThrowsOnUnknownEmail(): void
    {
        $this->expectException(InvalidCredentialsException::class);
        $this->makeHandler()->handle(new LoginUser('unknown@example.com', 'password123'));
    }

    public function testThrowsOnWrongPassword(): void
    {
        $this->expectException(InvalidCredentialsException::class);
        $this->makeHandler()->handle(new LoginUser('alice@example.com', 'wrongpasss'));
    }

    public function testEmailMatchIsCaseInsensitive(): void
    {
        $tokenService = $this->createMock(TokenServiceInterface::class);
        $tokenService->expects($this->once())->method('issue')->willReturn(new AuthToken('tok'));

        $this->makeHandler($tokenService)
            ->handle(new LoginUser('ALICE@EXAMPLE.COM', 'password123'));
    }
}
