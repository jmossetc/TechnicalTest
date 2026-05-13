<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Shop\Application\Handler;

use InvalidArgumentException;
use Mossetc\TechnicalTest\Company\Domain\Model\CompanyId;
use Mossetc\TechnicalTest\Shop\Application\Command\CreateShop;
use Mossetc\TechnicalTest\Shop\Domain\Exception\ShopAlreadyExistsException;
use Mossetc\TechnicalTest\Shop\Domain\Model\Shop;
use Mossetc\TechnicalTest\Shop\Domain\Model\ShopAddress;
use Mossetc\TechnicalTest\Shop\Domain\Model\ShopId;
use Mossetc\TechnicalTest\Shop\Domain\Model\ShopName;
use Mossetc\TechnicalTest\Shop\Domain\Repository\ShopRepositoryInterface;

final readonly class CreateShopHandler
{
    public function __construct(private ShopRepositoryInterface $repository) {}

    public function handle(CreateShop $command): ShopId
    {
        try {
            $companyId = new CompanyId($command->companyId);
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException("Invalid company ID: {$command->companyId}", previous: $e);
        }

        $name = new ShopName($command->name);

        if ($this->repository->findByNameAndCompany($name, $companyId) !== null) {
            throw new ShopAlreadyExistsException($name);
        }

        $id   = ShopId::generate();
        $shop = new Shop(
            id:          $id,
            companyId:   $companyId,
            name:        $name,
            address:     new ShopAddress(
                addressLine1: $command->addressLine1,
                addressLine2: $command->addressLine2,
                city:         $command->city,
                postalCode:   $command->postalCode,
                country:      $command->country,
            ),
            email:       $command->email,
            phoneNumber: $command->phoneNumber,
            latitude:    $command->latitude,
            longitude:   $command->longitude,
            isDigital:   $command->isDigital,
        );

        $this->repository->save($shop);

        return $id;
    }
}
