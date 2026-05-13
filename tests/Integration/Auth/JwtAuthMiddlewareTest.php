<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Integration\Auth;

use InvalidArgumentException;
use Mossetc\TechnicalTest\Auth\Domain\Model\Email;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserId;
use Mossetc\TechnicalTest\Auth\Domain\Exception\InvalidTokenException;
use Mossetc\TechnicalTest\Auth\Infrastructure\Jwt\JwtAuthMiddleware;
use Mossetc\TechnicalTest\Auth\Infrastructure\Jwt\JwtConfig;
use Mossetc\TechnicalTest\Auth\Infrastructure\Jwt\LcobucciJwtTokenService;
use PHPUnit\Framework\TestCase;

final class JwtAuthMiddlewareTest extends TestCase
{
    private LcobucciJwtTokenService $tokenService;
    private JwtAuthMiddleware $middleware;

    protected function setUp(): void
    {
        $this->tokenService = new LcobucciJwtTokenService(new JwtConfig(
            secret: 'super-secret-key-that-is-long-enough-32chars',
            issuer: 'https://example.com',
            audience: 'https://api.example.com',
        ));

        $this->middleware = new JwtAuthMiddleware($this->tokenService);
    }

    public function testAuthenticatesValidBearerToken(): void
    {
        $userId = UserId::generate();
        $token = $this->tokenService->issue($userId, new Email('user@example.com'));

        $recovered = $this->middleware->authenticate([
            'Authorization' => "Bearer {$token->value}",
        ]);

        $this->assertTrue($userId->equals($recovered));
    }

    public function testAuthenticatesLowercaseAuthorizationHeader(): void
    {
        $userId = UserId::generate();
        $token = $this->tokenService->issue($userId, new Email('user@example.com'));

        $recovered = $this->middleware->authenticate([
            'authorization' => "Bearer {$token->value}",
        ]);

        $this->assertTrue($userId->equals($recovered));
    }

    public function testThrowsWhenAuthorizationHeaderMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing Authorization header');

        $this->middleware->authenticate([]);
    }

    public function testThrowsWhenSchemeIsNotBearer(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Bearer scheme');

        $this->middleware->authenticate(['Authorization' => 'Basic dXNlcjpwYXNz']);
    }

    public function testThrowsOnInvalidToken(): void
    {
        $this->expectException(InvalidTokenException::class);

        $this->middleware->authenticate(['Authorization' => 'Bearer invalid.token.value']);
    }
}
