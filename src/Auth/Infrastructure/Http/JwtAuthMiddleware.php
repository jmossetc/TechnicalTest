<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Infrastructure\Http;

use InvalidArgumentException;
use Mossetc\TechnicalTest\Auth\Domain\Exception\InvalidTokenException;
use Mossetc\TechnicalTest\Auth\Domain\TokenServiceInterface;
use Mossetc\TechnicalTest\Auth\Domain\UserId;

final readonly class JwtAuthMiddleware
{
    public function __construct(private TokenServiceInterface $tokenService) {}

    /**
     * Extracts and validates the Bearer token from request headers.
     *
     * @param array<string, string> $headers HTTP headers (case-insensitive Authorization lookup)
     * @throws InvalidArgumentException when Authorization header is absent or malformed
     * @throws InvalidTokenException    when the token fails cryptographic or expiry validation
     */
    public function authenticate(array $headers): UserId
    {
        $authHeader = $headers['Authorization']
            ?? $headers['authorization']
            ?? throw new InvalidArgumentException('Missing Authorization header');

        if (!str_starts_with($authHeader, 'Bearer ')) {
            throw new InvalidArgumentException('Authorization header must use the Bearer scheme');
        }

        return $this->tokenService->validate(substr($authHeader, 7));
    }
}
