<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Domain\Model;

enum UserSortField: string
{
    case Email     = 'email';
    case FirstName = 'first_name';
    case LastName  = 'last_name';
    case Role      = 'role';
    case CreatedAt = 'created_at';
    case UpdatedAt = 'updated_at';
}
