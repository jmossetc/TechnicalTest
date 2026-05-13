<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Company\Application\Handler;

use InvalidArgumentException;
use Mossetc\TechnicalTest\Company\Application\Command\UpdateCompany;
use Mossetc\TechnicalTest\Company\Domain\Exception\CompanyAlreadyExistsException;
use Mossetc\TechnicalTest\Company\Domain\Exception\CompanyNotFoundException;
use Mossetc\TechnicalTest\Company\Domain\Model\Company;
use Mossetc\TechnicalTest\Company\Domain\Model\CompanyId;
use Mossetc\TechnicalTest\Company\Domain\Model\CompanyName;
use Mossetc\TechnicalTest\Company\Domain\Repository\CompanyRepositoryInterface;

final readonly class UpdateCompanyHandler
{
    public function __construct(private CompanyRepositoryInterface $repository) {}

    public function handle(UpdateCompany $command): void
    {
        try {
            $id = new CompanyId($command->id);
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException("Invalid company ID: {$command->id}", previous: $e);
        }

        $existing = $this->repository->findById($id);

        if ($existing === null) {
            throw new CompanyNotFoundException($command->id);
        }

        $newName = new CompanyName($command->name);

        $duplicate = $this->repository->findByName($newName);
        if ($duplicate !== null && !$duplicate->id->equals($id)) {
            throw new CompanyAlreadyExistsException($newName);
        }

        $this->repository->save(new Company(
            id:        $existing->id,
            name:      $newName,
            createdAt: $existing->createdAt,
        ));
    }
}
