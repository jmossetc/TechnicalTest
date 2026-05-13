<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Infrastructure\Jwt;

use InvalidArgumentException;

final readonly class JwtConfig
{
    /**
     * @param non-empty-string $secret
     * @param non-empty-string $issuer
     * @param non-empty-string $audience
     */
    public function __construct(
        public string $secret,
        public string $issuer,
        public string $audience,
        public int $ttlSeconds = 3600,
    ) {
        if (strlen($secret) < 32) {
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
    }
}
