<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Company\Application\Handler;

use Mossetc\TechnicalTest\Company\Application\Command\ListCompanies;
use Mossetc\TechnicalTest\Company\Application\DTO\PaginatedCompanies;
use Mossetc\TechnicalTest\Company\Domain\Repository\CompanyRepositoryInterface;

final readonly class ListCompaniesHandler
{
    public function __construct(private CompanyRepositoryInterface $repository) {}

    public function handle(ListCompanies $query): PaginatedCompanies
    {
        $offset = ($query->page - 1) * $query->limit;

        return new PaginatedCompanies(
            companies: $this->repository->findPaginatedByCriteria($query->criteria, $query->limit, $offset),
            total:     $this->repository->countByCriteria($query->criteria),
            page:      $query->page,
            limit:     $query->limit,
        );
    }
}
