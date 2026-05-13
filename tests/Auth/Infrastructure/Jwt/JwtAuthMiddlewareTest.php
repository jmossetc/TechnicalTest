<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Auth\Infrastructure\Jwt;

use InvalidArgumentException;
use Mossetc\TechnicalTest\Auth\Domain\Exception\InvalidTokenException;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserId;
use Mossetc\TechnicalTest\Auth\Domain\Service\TokenServiceInterface;
use Mossetc\TechnicalTest\Auth\Infrastructure\Jwt\JwtAuthMiddleware;
use PHPUnit\Framework\TestCase;

final class JwtAuthMiddlewareTest extends TestCase
{
    private function middleware(TokenServiceInterface $tokens): JwtAuthMiddleware
    {
        return new JwtAuthMiddleware($tokens);
    }

    private function tokenServiceReturning(UserId $id): TokenServiceInterface
    {
        $svc = $this->createStub(TokenServiceInterface::class);
        $svc->method('validate')->willReturn($id);

        return $svc;
    }

    public function testExtractsUserIdFromValidBearerToken(): void
    {
        $expected  = UserId::generate();
        $middleware = $this->middleware($this->tokenServiceReturning($expected));

        $result = $middleware->authenticate(['Authorization' => 'Bearer some.jwt.token']);

        $this->assertTrue($expected->equals($result));
    }

    public function testHeaderLookupIsCaseInsensitive(): void
    {
        $expected  = UserId::generate();
        $middleware = $this->middleware($this->tokenServiceReturning($expected));

        $result = $middleware->authenticate(['authorization' => 'Bearer some.jwt.token']);

        $this->assertTrue($expected->equals($result));
    }

    public function testThrowsWhenAuthorizationHeaderIsMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->middleware($this->createStub(TokenServiceInterface::class))
            ->authenticate([]);
    }

    public function testThrowsWhenSchemeIsNotBearer(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->middleware($this->createStub(TokenServiceInterface::class))
            ->authenticate(['Authorization' => 'Basic dXNlcjpwYXNz']);
    }

    public function testThrowsWhenTokenIsInvalid(): void
    {
        $svc = $this->createStub(TokenServiceInterface::class);
        $svc->method('validate')->willThrowException(new InvalidTokenException('bad'));

        $this->expectException(InvalidTokenException::class);
        $this->middleware($svc)->authenticate(['Authorization' => 'Bearer bad.token']);
    }

    public function testPassesOnlyTokenPartToService(): void
    {
        $svc = $this->createMock(TokenServiceInterface::class);
        $svc->expects($this->once())
            ->method('validate')
            ->with('my.actual.token')
            ->willReturn(UserId::generate());

        $this->middleware($svc)->authenticate(['Authorization' => 'Bearer my.actual.token']);
    }
}
