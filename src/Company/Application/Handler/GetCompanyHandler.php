<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Company\Application\Handler;

use InvalidArgumentException;
use Mossetc\TechnicalTest\Company\Domain\Exception\CompanyNotFoundException;
use Mossetc\TechnicalTest\Company\Domain\Model\Company;
use Mossetc\TechnicalTest\Company\Domain\Model\CompanyId;
use Mossetc\TechnicalTest\Company\Domain\Repository\CompanyRepositoryInterface;

final readonly class GetCompanyHandler
{
    public function __construct(private CompanyRepositoryInterface $repository) {}

    public function handle(string $companyId): Company
    {
        try {
            $id = new CompanyId($companyId);
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException("Invalid company ID: {$companyId}", previous: $e);
        }

        $company = $this->repository->findById($id);

        if ($company === null) {
            throw new CompanyNotFoundException($companyId);
        }

        return $company;
    }
}
