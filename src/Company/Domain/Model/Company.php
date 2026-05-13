<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Company\Domain\Model;

use DateTimeImmutable;

final readonly class Company
{
    public function __construct(
        public CompanyId $id,
        public CompanyName $name,
        public ?string $email = null,
        public ?string $phoneNumber = null,
        public ?string $website = null,
        public ?string $addressLine1 = null,
        public ?string $addressLine2 = null,
        public ?string $city = null,
        public ?string $postalCode = null,
        public ?string $country = null,
        public bool $isActive = true,
        public DateTimeImmutable $createdAt = new DateTimeImmutable(),
        public DateTimeImmutable $updatedAt = new DateTimeImmutable(),
        public ?DateTimeImmutable $deletedAt = null,
    ) {}
}
