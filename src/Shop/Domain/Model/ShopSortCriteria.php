<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Shop\Domain\Model;

use Mossetc\TechnicalTest\Shared\Domain\SortDirection;

final readonly class ShopSortCriteria
{
    public function __construct(
        public ShopSortField $field     = ShopSortField::Name,
        public SortDirection $direction = SortDirection::Asc,
    ) {}
}
