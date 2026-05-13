<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Domain;

final readonly class User
{
    public function __construct(
        public UserId $id,
        public Email $email,
        public HashedPassword $password,
    ) {}

    public function verifyPassword(PlainPassword $password): bool
    {
        return $this->password->verify($password);
    }
}
