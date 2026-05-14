<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Company\Domain\Model;

enum CompanySortField: string
{
    case Name      = 'name';
    case City      = 'city';
    case Country   = 'country';
    case CreatedAt = 'created_at';
    case UpdatedAt = 'updated_at';
}
