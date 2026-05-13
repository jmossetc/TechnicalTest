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
        public DateTimeImmutable $createdAt = new DateTimeImmutable(),
        public DateTimeImmutable $updatedAt = new DateTimeImmutable(),
        public ?DateTimeImmutable $deletedAt = null,
    ) {}
}
