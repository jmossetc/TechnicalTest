<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Domain;

enum Role: string
{
    case Admin = 'admin';
    case CompanyManager = 'company_manager';
    case ShopManager = 'shop_manager';
}
