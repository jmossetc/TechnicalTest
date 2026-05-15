<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Auth\Presentation\Controller;

use Mossetc\TechnicalTest\Auth\Application\Command\RegisterUser;
use Mossetc\TechnicalTest\Auth\Application\Handler\LoginUserHandler;
use Mossetc\TechnicalTest\Auth\Application\Handler\RegisterUserHandler;
use Mossetc\TechnicalTest\Auth\Domain\Model\AuthToken;
use Mossetc\TechnicalTest\Auth\Domain\Service\TokenServiceInterface;
use Mossetc\TechnicalTest\Auth\Infrastructure\Security\PasswordHasher;
use Mossetc\TechnicalTest\Auth\Presentation\Controller\LoginUserController;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Request;
use Mossetc\TechnicalTest\Tests\Support\InMemoryUserRepository;
use PHPUnit\Framework\TestCase;

final class LoginUserControllerTest extends TestCase
{
    private InMemoryUserRepository $userRepo;
    private PasswordHasher $hasher;

    protected function setUp(): void
    {
        $this->userRepo = new InMemoryUserRepository();
        $this->hasher   = new PasswordHasher('');

        new RegisterUserHandler($this->userRepo, $this->hasher)
            ->handle(new RegisterUser('alice@example.com', 'password123', 'Alice', 'Smith'));
    }

    private function ctrl(?TokenServiceInterface $tokens = null): LoginUserController
    {
        $tokens ??= self::createStub(TokenServiceInterface::class);

        return new LoginUserController(
            new LoginUserHandler($this->userRepo, $tokens, $this->hasher),
        );
    }

    private function req(string $email, string $password): Request
    {
        return new Request('POST', '/api/auth/login', [], ['email' => $email, 'password' => $password]);
    }

    public function testReturnsTokenOnSuccess(): void
    {
        $tokens = self::createStub(TokenServiceInterface::class);
        $tokens->method('issue')->willReturn(new AuthToken('the.jwt.token'));

        $response = $this->ctrl($tokens)($this->req('alice@example.com', 'password123'));

        self::assertSame(200, $response->status());
        self::assertSame('the.jwt.token', $response->data()['token'] ?? null);
    }

    public function testReturns422WhenEmailIsMissing(): void
    {
        $response = $this->ctrl()($this->req('', 'password123'));

        self::assertSame(422, $response->status());
    }

    public function testReturns422WhenPasswordIsMissing(): void
    {
        $response = $this->ctrl()($this->req('alice@example.com', ''));

        self::assertSame(422, $response->status());
    }

    public function testReturns401OnInvalidCredentials(): void
    {
        $response = $this->ctrl()($this->req('alice@example.com', 'wrongpasss1'));

        self::assertSame(401, $response->status());
    }

    public function testReturns401OnUnknownEmail(): void
    {
        $response = $this->ctrl()($this->req('ghost@example.com', 'password123'));

        self::assertSame(401, $response->status());
    }
}
