<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Shop\Domain\Model;

enum ShopSortField: string
{
    case CompanyId  = 'company_id';
    case Name       = 'name';
    case Email      = 'email';
    case City       = 'city';
    case PostalCode = 'postal_code';
    case Country    = 'country';
    case IsActive   = 'is_active';
    case CreatedAt  = 'created_at';
    case UpdatedAt  = 'updated_at';
}
