<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Application\DTO;

use Mossetc\TechnicalTest\Auth\Domain\Model\Role;

final readonly class RegistrationInput
{
    public function __construct(
        public Role $role,
        public ?string $companyId,
        public ?string $shopId,
        public ?string $phoneNumber,
        public string $firstName,
        public string $lastName,
        public string $email,
        public string $password,
    ) {}
}
