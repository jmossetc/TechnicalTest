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

        // Null in command means "not provided" — preserve the existing value
        $this->repository->save(new Company(
            id:           $existing->id,
            name:         $newName,
            email:        $command->email        ?? $existing->email,
            phoneNumber:  $command->phoneNumber  ?? $existing->phoneNumber,
            website:      $command->website      ?? $existing->website,
            addressLine1: $command->addressLine1 ?? $existing->addressLine1,
            addressLine2: $command->addressLine2 ?? $existing->addressLine2,
            city:         $command->city         ?? $existing->city,
            postalCode:   $command->postalCode   ?? $existing->postalCode,
            country:      $command->country      ?? $existing->country,
            isActive:     $command->isActive     ?? $existing->isActive,
            createdAt:    $existing->createdAt,
        ));
    }
}
