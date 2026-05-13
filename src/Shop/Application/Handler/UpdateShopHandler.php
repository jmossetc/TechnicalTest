<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Shop\Application\Handler;

use InvalidArgumentException;
use Mossetc\TechnicalTest\Shop\Application\Command\UpdateShop;
use Mossetc\TechnicalTest\Shop\Domain\Exception\ShopAlreadyExistsException;
use Mossetc\TechnicalTest\Shop\Domain\Exception\ShopNotFoundException;
use Mossetc\TechnicalTest\Shop\Domain\Model\Shop;
use Mossetc\TechnicalTest\Shop\Domain\Model\ShopAddress;
use Mossetc\TechnicalTest\Shop\Domain\Model\ShopId;
use Mossetc\TechnicalTest\Shop\Domain\Model\ShopName;
use Mossetc\TechnicalTest\Shop\Domain\Repository\ShopRepositoryInterface;

final readonly class UpdateShopHandler
{
    public function __construct(private ShopRepositoryInterface $repository) {}

    public function handle(UpdateShop $command): void
    {
        try {
            $id = new ShopId($command->id);
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException("Invalid shop ID: {$command->id}", previous: $e);
        }

        $existing = $this->repository->findById($id);

        if ($existing === null) {
            throw new ShopNotFoundException($command->id);
        }

        $newName = new ShopName($command->name);

        $duplicate = $this->repository->findByNameAndCompany($newName, $existing->companyId);
        if ($duplicate !== null && !$duplicate->id->equals($id)) {
            throw new ShopAlreadyExistsException($newName);
        }

        // Null in command means "not provided" — preserve the existing value
        $this->repository->save(new Shop(
            id:          $existing->id,
            companyId:   $existing->companyId,
            name:        $newName,
            address:     new ShopAddress(
                addressLine1: $command->addressLine1 ?? $existing->address->addressLine1,
                addressLine2: $command->addressLine2 ?? $existing->address->addressLine2,
                city:         $command->city         ?? $existing->address->city,
                postalCode:   $command->postalCode   ?? $existing->address->postalCode,
                country:      $command->country      ?? $existing->address->country,
            ),
            email:       $command->email       ?? $existing->email,
            phoneNumber: $command->phoneNumber ?? $existing->phoneNumber,
            latitude:    $command->latitude    ?? $existing->latitude,
            longitude:   $command->longitude   ?? $existing->longitude,
            isDigital:   $command->isDigital   ?? $existing->isDigital,
            isActive:    $command->isActive    ?? $existing->isActive,
            createdAt:   $existing->createdAt,
        ));
    }
}
