<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Infrastructure\Security;

use Mossetc\TechnicalTest\Auth\Domain\Model\HashedPassword;
use Mossetc\TechnicalTest\Auth\Domain\Model\PlainPassword;
use Mossetc\TechnicalTest\Auth\Domain\Service\PasswordHasherInterface;

final readonly class PasswordHasher implements PasswordHasherInterface
{
    /** @var non-empty-string|null */
    private ?string $salt;

    public function __construct(string $salt)
    {
        $this->salt = $salt !== '' ? $salt : null;
    }

    public function hash(PlainPassword $password): HashedPassword
    {
        return HashedPassword::fromHash(
            password_hash($this->salted($password), PASSWORD_BCRYPT),
        );
    }

    public function verify(PlainPassword $password, HashedPassword $hash): bool
    {
        return password_verify($this->salted($password), $hash->hash);
    }

    private function salted(PlainPassword $password): string
    {
        return $this->salt !== null ? $this->salt . $password->value : $password->value;
    }
}
