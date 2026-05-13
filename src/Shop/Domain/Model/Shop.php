<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Shop\Domain\Model;

use DateTimeImmutable;
use Mossetc\TechnicalTest\Company\Domain\Model\CompanyId;

final readonly class Shop
{
    public function __construct(
        public ShopId $id,
        public CompanyId $companyId,
        public ShopName $name,
        public ShopAddress $address = new ShopAddress(),
        public ?string $email = null,
        public ?string $phoneNumber = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public bool $isDigital = false,
        public bool $isActive = true,
        public DateTimeImmutable $createdAt = new DateTimeImmutable(),
        public DateTimeImmutable $updatedAt = new DateTimeImmutable(),
        public ?DateTimeImmutable $deletedAt = null,
    ) {}
}
