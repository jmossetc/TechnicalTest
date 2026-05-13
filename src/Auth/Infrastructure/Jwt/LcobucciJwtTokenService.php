<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Infrastructure\Jwt;

use DateTimeImmutable;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\LooseValidAt;
use Lcobucci\JWT\Validation\Constraint\PermittedFor;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\RequiredConstraintsViolated;
use Mossetc\TechnicalTest\Auth\Domain\Model\AuthToken;
use Mossetc\TechnicalTest\Auth\Domain\Model\Email;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserId;
use Mossetc\TechnicalTest\Auth\Domain\Exception\InvalidTokenException;
use Mossetc\TechnicalTest\Auth\Domain\Service\TokenServiceInterface;
use Mossetc\TechnicalTest\Shared\Infrastructure\Clock\SystemClock;
use Psr\Clock\ClockInterface;
use Throwable;

final class LcobucciJwtTokenService implements TokenServiceInterface
{
    private readonly Configuration $jwtConfiguration;

    public function __construct(
        private readonly JwtConfig $config,
        private readonly ClockInterface $clock = new SystemClock(),
    ) {
        $signer = new Sha256();
        $key = InMemory::plainText($config->secret);

        $this->jwtConfiguration = Configuration::forSymmetricSigner($signer, $key);

        $this->jwtConfiguration->setValidationConstraints(
            new SignedWith($signer, $key),
            new IssuedBy($config->issuer),
            new PermittedFor($config->audience),
            new LooseValidAt($this->clock),
        );
    }

    public function issue(UserId $userId, Email $email): AuthToken
    {
        $now = $this->clock->now();
        $expiresAt = $now->modify("+{$this->config->ttlSeconds} seconds");

        if (!$expiresAt instanceof DateTimeImmutable) {
            throw new \RuntimeException('Failed to compute token expiry');
        }

        $token = $this->jwtConfiguration->builder()
            ->issuedBy($this->config->issuer)
            ->permittedFor($this->config->audience)
            ->identifiedBy(bin2hex(random_bytes(16)))
            ->issuedAt($now)
            ->expiresAt($expiresAt)
            ->withClaim('user_id', $userId->value)
            ->withClaim('email', $email->value)
            ->getToken($this->jwtConfiguration->signer(), $this->jwtConfiguration->signingKey());

        return new AuthToken($token->toString());
    }

    public function validate(string $token): UserId
    {
        if ($token === '') {
            throw new InvalidTokenException('token must not be empty');
        }

        try {
            $parsed = $this->jwtConfiguration->parser()->parse($token);
        } catch (Throwable $e) {
            throw new InvalidTokenException('malformed token', previous: $e);
        }

        if (!$parsed instanceof Plain) {
            throw new InvalidTokenException('unexpected token type');
        }

        try {
            $this->jwtConfiguration->validator()->assert(
                $parsed,
                ...$this->jwtConfiguration->validationConstraints(),
            );
        } catch (RequiredConstraintsViolated $e) {
            throw new InvalidTokenException($e->getMessage(), previous: $e);
        }

        $userId = $parsed->claims()->get('user_id');

        if (!is_string($userId) || $userId === '') {
            throw new InvalidTokenException('missing or invalid user_id claim');
        }

        return new UserId($userId);
    }
}
