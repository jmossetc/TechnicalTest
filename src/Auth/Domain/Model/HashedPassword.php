<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Domain\Model;

use InvalidArgumentException;

final readonly class HashedPassword
{
    private function __construct(public string $hash) {}

    public static function fromPlain(PlainPassword $password): self
    {
        return new self(password_hash($password->value, PASSWORD_BCRYPT));
    }

    public static function fromHash(string $hash): self
    {
        if (strlen($hash) < 20) {
            throw new InvalidArgumentException('Invalid password hash');
        }

        return new self($hash);
    }

    public function verify(PlainPassword $password): bool
    {
        return password_verify($password->value, $this->hash);
    }
}
