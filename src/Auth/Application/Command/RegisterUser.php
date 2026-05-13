<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Application\Command;

final readonly class RegisterUser
{
    public function __construct(
        public string $email,
        public string $password,
        public string $firstName,
        public string $lastName,
        public string $role = 'employee',
        public ?string $companyId = null,
        public ?string $shopId = null,
        public ?string $phoneNumber = null,
    ) {}
}
