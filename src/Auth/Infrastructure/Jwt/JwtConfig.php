<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Infrastructure\Jwt;

use InvalidArgumentException;

final readonly class JwtConfig
{
    /** @var non-empty-string */
    public string $secret;
    /** @var non-empty-string */
    public string $issuer;
    /** @var non-empty-string */
    public string $audience;
    public int $ttlSeconds;

    public function __construct(
        string $secret,
        string $issuer,
        string $audience,
        int $ttlSeconds = 3600,
    ) {
        if (\strlen($secret) < 32) {
            throw new InvalidArgumentException('JWT secret must be at least 32 characters long');
        }

        if ($issuer === '') {
            throw new InvalidArgumentException('JWT issuer cannot be empty');
        }

        if ($audience === '') {
            throw new InvalidArgumentException('JWT audience cannot be empty');
        }

        if ($ttlSeconds <= 0) {
            throw new InvalidArgumentException('JWT TTL must be positive');
        }

        $this->secret    = $secret;
        $this->issuer    = $issuer;
        $this->audience  = $audience;
        $this->ttlSeconds = $ttlSeconds;
    }
}
