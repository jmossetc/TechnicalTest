<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Application\Command;

use InvalidArgumentException;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserScope;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserSearchCriteria;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserSortCriteria;

final readonly class ListUsers
{
    public function __construct(
        public int                $page = 1,
        public int                $limit = 10,
        public UserScope          $scope = new UserScope(),
        public UserSearchCriteria $criteria = new UserSearchCriteria(),
        public UserSortCriteria   $sort = new UserSortCriteria(),
    ) {
        if ($this->page < 1) {
            throw new InvalidArgumentException('Page must be at least 1');
        }

        if ($this->limit < 1 || $this->limit > 100) {
            throw new InvalidArgumentException('Limit must be between 1 and 100');
        }
    }
}
