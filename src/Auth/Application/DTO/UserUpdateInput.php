<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Application\DTO;

final readonly class UserUpdateInput
{
    public function __construct(
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?string $email = null,
        public ?string $phoneNumber = null,
        public ?string $password = null,
        public ?string $currentPassword = null,
        public ?bool   $isActive = null,
        public ?string $role = null,
        public ?string $companyId = null,
        public ?string $shopId = null,
    ) {}
}
