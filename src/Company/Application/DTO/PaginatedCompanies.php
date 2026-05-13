<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Company\Application\DTO;

use Mossetc\TechnicalTest\Company\Domain\Model\Company;

final readonly class PaginatedCompanies
{
    /**
     * @param list<Company> $companies
     */
    public function __construct(
        public array $companies,
        public int $total,
        public int $page,
        public int $limit,
    ) {}

    public function pages(): int
    {
        return $this->limit > 0 ? (int) ceil($this->total / $this->limit) : 0;
    }
}
