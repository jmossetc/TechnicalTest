<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Domain\Model;

use DateTimeImmutable;

final readonly class User
{
    public function __construct(
        public UserId $id,
        public Email $email,
        public HashedPassword $password,
        public FirstName $firstName,
        public LastName $lastName,
        public Role $role = Role::Employee,
        public ?string $companyId = null,
        public ?string $shopId = null,
        public ?string $phoneNumber = null,
        public bool $isActive = true,
        public ?DateTimeImmutable $lastLoginAt = null,
        public DateTimeImmutable $createdAt = new DateTimeImmutable(),
        public DateTimeImmutable $updatedAt = new DateTimeImmutable(),
        public ?DateTimeImmutable $deletedAt = null,
    ) {}

    public function verifyPassword(PlainPassword $password): bool
    {
        return $this->password->verify($password);
    }
}
