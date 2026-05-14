<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Domain\Model;

use DateTimeImmutable;

final readonly class User implements \JsonSerializable
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

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'id'            => $this->id->value,
            'email'         => $this->email->value,
            'first_name'    => $this->firstName->value,
            'last_name'     => $this->lastName->value,
            'phone_number'  => $this->phoneNumber,
            'role'          => $this->role->value,
            'company_id'    => $this->companyId,
            'shop_id'       => $this->shopId,
            'is_active'     => $this->isActive,
            'last_login_at' => $this->lastLoginAt?->format('Y-m-d\TH:i:s\Z'),
            'created_at'    => $this->createdAt->format('Y-m-d\TH:i:s\Z'),
            'updated_at'    => $this->updatedAt->format('Y-m-d\TH:i:s\Z'),
        ];
    }
}
