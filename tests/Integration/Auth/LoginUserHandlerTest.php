<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Integration\Auth;

use Mossetc\TechnicalTest\Auth\Application\Handler\LoginUserHandler;
use Mossetc\TechnicalTest\Auth\Application\Handler\RegisterUserHandler;
use Mossetc\TechnicalTest\Auth\Application\Query\LoginUser;
use Mossetc\TechnicalTest\Auth\Application\Query\RegisterUser;
use Mossetc\TechnicalTest\Auth\Domain\AuthToken;
use Mossetc\TechnicalTest\Auth\Domain\Email;
use Mossetc\TechnicalTest\Auth\Domain\Exception\InvalidCredentialsException;
use Mossetc\TechnicalTest\Auth\Domain\TokenServiceInterface;
use Mossetc\TechnicalTest\Auth\Domain\UserId;
use Mossetc\TechnicalTest\Auth\Infrastructure\Repository\PdoUserRepository;
use Mossetc\TechnicalTest\Tests\Integration\Support\DatabaseTestCase;

final class LoginUserHandlerTest extends DatabaseTestCase
{
    private PdoUserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new PdoUserRepository($this->pdo);

        $roleRepo = new \Mossetc\TechnicalTest\Auth\Infrastructure\Repository\PdoUserRoleRepository($this->pdo);
        (new RegisterUserHandler($this->repository, $roleRepo))
            ->handle(new RegisterUser('alice@example.com', 'password123'));
    }

    private function makeHandler(?TokenServiceInterface $tokenService = null): LoginUserHandler
    {
        return new LoginUserHandler(
            $this->repository,
            $tokenService ?? $this->createStub(TokenServiceInterface::class),
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
