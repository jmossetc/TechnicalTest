<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Auth\Infrastructure\Jwt;

use DateTimeImmutable;
use Mossetc\TechnicalTest\Auth\Domain\Exception\InvalidTokenException;
use Mossetc\TechnicalTest\Auth\Domain\Model\Email;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserId;
use Mossetc\TechnicalTest\Auth\Infrastructure\Jwt\JwtConfig;
use Mossetc\TechnicalTest\Auth\Infrastructure\Jwt\LcobucciJwtTokenService;
use Mossetc\TechnicalTest\Shared\Infrastructure\Clock\SystemClock;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

final class LcobucciJwtTokenServiceTest extends TestCase
{
    private const string SECRET   = 'test-secret-key-at-least-32-chars!!';
    private const string ISSUER   = 'http://localhost';
    private const string AUDIENCE = 'http://localhost';

    private function config(int $ttl = 3600): JwtConfig
    {
        return new JwtConfig(self::SECRET, self::ISSUER, self::AUDIENCE, $ttl);
    }

    private function service(?JwtConfig $config = null, ?ClockInterface $clock = null): LcobucciJwtTokenService
    {
        return new LcobucciJwtTokenService(
            $config ?? $this->config(),
            $clock ?? new SystemClock(),
        );
    }

    private function fixedClock(string $time): ClockInterface
    {
        $dt = new DateTimeImmutable($time);

        return new class ($dt) implements ClockInterface {
            public function __construct(private readonly DateTimeImmutable $time) {}
            public function now(): DateTimeImmutable { return $this->time; }
        };
    }

    public function testIssueProducesNonEmptyToken(): void
    {
        $token = $this->service()->issue(UserId::generate(), new Email('user@example.com'));

        $this->assertNotEmpty($token->value);
    }

    public function testIssuedTokenCanBeValidated(): void
    {
        $userId  = UserId::generate();
        $service = $this->service();

        $token  = $service->issue($userId, new Email('user@example.com'));
        $result = $service->validate($token->value);

        $this->assertTrue($userId->equals($result));
    }

    public function testTwoTokensForSameUserAreDifferent(): void
    {
        $service = $this->service();
        $userId  = UserId::generate();
        $email   = new Email('user@example.com');

        $a = $service->issue($userId, $email);
        $b = $service->issue($userId, $email);

        $this->assertNotSame($a->value, $b->value);
    }

    public function testValidateThrowsForEmptyToken(): void
    {
        $this->expectException(InvalidTokenException::class);
        $this->service()->validate('');
    }

    public function testValidateThrowsForMalformedToken(): void
    {
        $this->expectException(InvalidTokenException::class);
        $this->service()->validate('not.a.jwt');
    }

    public function testValidateThrowsForExpiredToken(): void
    {
        // Issue with a clock 2 hours in the past, TTL 1 second → already expired
        $token = $this->service(config: $this->config(ttl: 1), clock: $this->fixedClock('-7200 seconds'))
            ->issue(UserId::generate(), new Email('u@example.com'));

        // Validate with real current time: token has expired
        $this->expectException(InvalidTokenException::class);
        $this->service()->validate($token->value);
    }

    public function testValidateThrowsForTokenSignedWithDifferentSecret(): void
    {
        $otherConfig = new JwtConfig(str_repeat('x', 32), self::ISSUER, self::AUDIENCE);
        $token       = $this->service(config: $otherConfig)
            ->issue(UserId::generate(), new Email('u@example.com'));

        $this->expectException(InvalidTokenException::class);
        $this->service()->validate($token->value);
    }
}
