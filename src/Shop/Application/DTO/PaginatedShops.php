<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Shop\Application\DTO;

use Mossetc\TechnicalTest\Shop\Domain\Model\Shop;

final readonly class PaginatedShops
{
    /**
     * @param list<Shop> $shops
     */
    public function __construct(
        public array $shops,
        public int $total,
        public int $page,
        public int $limit,
    ) {}

    public function pages(): int
    {
        return $this->limit > 0 ? (int) ceil($this->total / $this->limit) : 0;
    }
}
