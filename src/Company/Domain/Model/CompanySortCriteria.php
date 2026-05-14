<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Company\Domain\Model;

use Mossetc\TechnicalTest\Shared\Domain\SortDirection;

final readonly class CompanySortCriteria
{
    public function __construct(
        public CompanySortField $field     = CompanySortField::Name,
        public SortDirection    $direction = SortDirection::Asc,
    ) {}
}
