<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Integration\Auth;

use DateTimeImmutable;
use Mossetc\TechnicalTest\Auth\Domain\Model\Email;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserId;
use Mossetc\TechnicalTest\Auth\Domain\Exception\InvalidTokenException;
use Mossetc\TechnicalTest\Auth\Infrastructure\Jwt\JwtConfig;
use Mossetc\TechnicalTest\Auth\Infrastructure\Jwt\LcobucciJwtTokenService;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

final class LcobucciJwtTokenServiceTest extends TestCase
{
    private const string SECRET = 'super-secret-key-that-is-long-enough-32chars';
    private const string ISSUER = 'https://example.com';
    private const string AUDIENCE = 'https://api.example.com';

    private JwtConfig $config;
    private LcobucciJwtTokenService $service;

    protected function setUp(): void
    {
        $this->config = new JwtConfig(
            secret: self::SECRET,
            issuer: self::ISSUER,
            audience: self::AUDIENCE,
            ttlSeconds: 3600,
        );

        $this->service = new LcobucciJwtTokenService($this->config);
    }

    public function testIssuesNonEmptyToken(): void
    {
        $token = $this->service->issue(UserId::generate(), new Email('user@example.com'));

        $this->assertNotEmpty($token->value);
        $this->assertStringContainsString('.', $token->value);
    }

    public function testValidatesIssuedToken(): void
    {
        $userId = UserId::generate();
        $token = $this->service->issue($userId, new Email('user@example.com'));

        $recovered = $this->service->validate($token->value);

        $this->assertTrue($userId->equals($recovered));
    }

    public function testRejectsTamperedToken(): void
    {
        $token = $this->service->issue(UserId::generate(), new Email('user@example.com'));
        $tampered = $token->value . 'tampered';

        $this->expectException(InvalidTokenException::class);

        $this->service->validate($tampered);
    }

    public function testRejectsMalformedToken(): void
    {
        $this->expectException(InvalidTokenException::class);

        $this->service->validate('not.a.jwt');
    }

    public function testRejectsEmptyString(): void
    {
        $this->expectException(InvalidTokenException::class);

        $this->service->validate('');
    }

    public function testRejectsExpiredToken(): void
    {
        $frozenPast = new DateTimeImmutable('-2 hours');

        $clock = new class ($frozenPast) implements ClockInterface {
            public function __construct(private readonly DateTimeImmutable $time) {}

            public function now(): DateTimeImmutable
            {
                return $this->time;
            }
        };

        $pastService = new LcobucciJwtTokenService(
            new JwtConfig(self::SECRET, self::ISSUER, self::AUDIENCE, ttlSeconds: 1),
            $clock,
        );

        $token = $pastService->issue(UserId::generate(), new Email('user@example.com'));

        $this->expectException(InvalidTokenException::class);

        $this->service->validate($token->value);
    }

    public function testRejectsTokenWithWrongSecret(): void
    {
        $otherService = new LcobucciJwtTokenService(
            new JwtConfig(
                secret: 'a-completely-different-secret-key-32chars',
                issuer: self::ISSUER,
                audience: self::AUDIENCE,
            ),
        );

        $token = $otherService->issue(UserId::generate(), new Email('user@example.com'));

        $this->expectException(InvalidTokenException::class);

        $this->service->validate($token->value);
    }

    public function testRejectsTokenWithWrongIssuer(): void
    {
        $otherService = new LcobucciJwtTokenService(
            new JwtConfig(
                secret: self::SECRET,
                issuer: 'https://other.example.com',
                audience: self::AUDIENCE,
            ),
        );

        $token = $otherService->issue(UserId::generate(), new Email('user@example.com'));

        $this->expectException(InvalidTokenException::class);

        $this->service->validate($token->value);
    }

    public function testRejectsTokenWithWrongAudience(): void
    {
        $otherService = new LcobucciJwtTokenService(
            new JwtConfig(
                secret: self::SECRET,
                issuer: self::ISSUER,
                audience: 'https://other-api.example.com',
            ),
        );

        $token = $otherService->issue(UserId::generate(), new Email('user@example.com'));

        $this->expectException(InvalidTokenException::class);

        $this->service->validate($token->value);
    }

    public function testIssuesUniqueTokensForSameUser(): void
    {
        $userId = UserId::generate();
        $email = new Email('user@example.com');

        $token1 = $this->service->issue($userId, $email);
        $token2 = $this->service->issue($userId, $email);

        $this->assertNotSame($token1->value, $token2->value);
    }
}
