<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Domain\Model;

use Mossetc\TechnicalTest\Shared\Domain\SortDirection;

final readonly class UserSortCriteria
{
    public function __construct(
        public UserSortField $field     = UserSortField::Email,
        public SortDirection $direction = SortDirection::Asc,
    ) {}
}
