<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Infrastructure\Security;

use Mossetc\TechnicalTest\Auth\Domain\Model\HashedPassword;
use Mossetc\TechnicalTest\Auth\Domain\Model\PlainPassword;
use Mossetc\TechnicalTest\Auth\Domain\Service\PasswordHasherInterface;

final readonly class PasswordHasher implements PasswordHasherInterface
{
    /** @var non-empty-string|null */
    private ?string $pepper;

    public function __construct(string $pepper)
    {
        $this->pepper = $pepper !== '' ? $pepper : null;
    }

    public function hash(PlainPassword $password): HashedPassword
    {
        return HashedPassword::fromHash(
            password_hash($this->peppered($password), PASSWORD_BCRYPT),
        );
    }

    public function verify(PlainPassword $password, HashedPassword $hash): bool
    {
        return password_verify($this->peppered($password), $hash->hash);
    }

    private function peppered(PlainPassword $password): string
    {
        return $this->pepper !== null ? $this->pepper . $password->value : $password->value;
    }
}
