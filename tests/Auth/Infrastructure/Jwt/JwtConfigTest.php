<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Auth\Infrastructure\Jwt;

use InvalidArgumentException;
use Mossetc\TechnicalTest\Auth\Infrastructure\Jwt\JwtConfig;
use PHPUnit\Framework\TestCase;

final class JwtConfigTest extends TestCase
{
    private const string VALID_SECRET = 'a-valid-secret-with-at-least-32-chars';

    public function testAcceptsValidConfig(): void
    {
        $config = new JwtConfig(self::VALID_SECRET, 'https://issuer.test', 'https://audience.test', 3600);

        self::assertSame(self::VALID_SECRET, $config->secret);
        self::assertSame('https://issuer.test', $config->issuer);
        self::assertSame('https://audience.test', $config->audience);
        self::assertSame(3600, $config->ttlSeconds);
    }

    public function testRejectsSecretShorterThan32Characters(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new JwtConfig('short-secret', 'https://issuer.test', 'https://audience.test');
    }

    public function testAcceptsSecretOfExactly32Characters(): void
    {
        $secret = str_repeat('a', 32);
        $config = new JwtConfig($secret, 'http://i', 'http://a');

        self::assertSame($secret, $config->secret);
    }

    public function testRejectsEmptyIssuer(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new JwtConfig(self::VALID_SECRET, '', 'https://audience.test');
    }

    public function testRejectsEmptyAudience(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new JwtConfig(self::VALID_SECRET, 'https://issuer.test', '');
    }

    public function testRejectsZeroTtl(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new JwtConfig(self::VALID_SECRET, 'http://i', 'http://a', 0);
    }

    public function testRejectsNegativeTtl(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new JwtConfig(self::VALID_SECRET, 'http://i', 'http://a', -1);
    }

    public function testDefaultTtlIs3600(): void
    {
        $config = new JwtConfig(self::VALID_SECRET, 'http://i', 'http://a');

        self::assertSame(3600, $config->ttlSeconds);
    }
}
