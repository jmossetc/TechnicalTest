<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Shared\Domain;

enum SortDirection: string
{
    case Asc  = 'asc';
    case Desc = 'desc';
}
