<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Company\Application\Handler;

use InvalidArgumentException;
use Mossetc\TechnicalTest\Company\Domain\Exception\CompanyNotFoundException;
use Mossetc\TechnicalTest\Company\Domain\Model\CompanyId;
use Mossetc\TechnicalTest\Company\Domain\Repository\CompanyRepositoryInterface;

final readonly class DeleteCompanyHandler
{
    public function __construct(private CompanyRepositoryInterface $repository) {}

    public function handle(string $companyId): void
    {
        try {
            $id = new CompanyId($companyId);
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException("Invalid company ID: {$companyId}", previous: $e);
        }

        if ($this->repository->findById($id) === null) {
            throw new CompanyNotFoundException($companyId);
        }

        $this->repository->delete($id);
    }
}
