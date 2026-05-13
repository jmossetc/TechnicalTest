<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Domain\Model;

final readonly class UserRole
{
    public function __construct(
        public Role $role,
        public ?string $companyId = null,
        public ?string $shopId = null,
    ) {}
}
