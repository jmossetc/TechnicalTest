<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Domain\Model;

enum Role: string
{
    case Admin        = 'admin';
    case CompanyAdmin = 'company_admin';
    case ShopManager  = 'shop_manager';
    case Employee     = 'employee';
}
