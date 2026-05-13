<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Application\Query;

final readonly class RegisterUser
{
    public function __construct(
        public string $email,
        public string $password,
        public ?string $role = null,
        public ?string $companyId = null,
        public ?string $shopId = null,
    ) {}
}
