<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Domain\Model;

final readonly class UserUpdatePermissions
{
    public function __construct(
        public bool $canEditProfile, // first_name, last_name, email, phone_number
        public bool $canEditStatus,  // is_active
        public bool $canEditRole,    // role, company_id, shop_id (and password for non-self callers)
    ) {}
}
