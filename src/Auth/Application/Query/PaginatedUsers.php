<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Application\Query;

use Mossetc\TechnicalTest\Auth\Domain\User;

final readonly class PaginatedUsers
{
    /**
     * @param list<User> $users
     */
    public function __construct(
        public array $users,
        public int $total,
        public int $page,
        public int $limit,
    ) {}

    public function pages(): int
    {
        return $this->limit > 0 ? (int) ceil($this->total / $this->limit) : 0;
    }
}
