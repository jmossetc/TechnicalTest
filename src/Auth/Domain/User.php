<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Domain;

use DateTimeImmutable;

final readonly class User
{
    public function __construct(
        public UserId $id,
        public Email $email,
        public HashedPassword $password,
        public DateTimeImmutable $createdAt = new DateTimeImmutable(),
        public DateTimeImmutable $updatedAt = new DateTimeImmutable(),
        public ?DateTimeImmutable $deletedAt = null,
    ) {}

    public function verifyPassword(PlainPassword $password): bool
    {
        return $this->password->verify($password);
    }
}
