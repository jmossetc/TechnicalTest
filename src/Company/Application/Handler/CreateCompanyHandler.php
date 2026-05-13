<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Company\Application\Handler;

use Mossetc\TechnicalTest\Company\Application\Command\CreateCompany;
use Mossetc\TechnicalTest\Company\Domain\Exception\CompanyAlreadyExistsException;
use Mossetc\TechnicalTest\Company\Domain\Model\Company;
use Mossetc\TechnicalTest\Company\Domain\Model\CompanyId;
use Mossetc\TechnicalTest\Company\Domain\Model\CompanyName;
use Mossetc\TechnicalTest\Company\Domain\Repository\CompanyRepositoryInterface;

final readonly class CreateCompanyHandler
{
    public function __construct(private CompanyRepositoryInterface $repository) {}

    public function handle(CreateCompany $command): CompanyId
    {
        $name = new CompanyName($command->name);

        if ($this->repository->findByName($name) !== null) {
            throw new CompanyAlreadyExistsException($name);
        }

        $id = CompanyId::generate();

        $this->repository->save(new Company(
            id:           $id,
            name:         $name,
            email:        $command->email,
            phoneNumber:  $command->phoneNumber,
            website:      $command->website,
            addressLine1: $command->addressLine1,
            addressLine2: $command->addressLine2,
            city:         $command->city,
            postalCode:   $command->postalCode,
            country:      $command->country,
        ));

        return $id;
    }
}
