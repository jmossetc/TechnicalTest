<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Company\Application\Command;

final readonly class UpdateCompany
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $email = null,
        public ?string $phoneNumber = null,
        public ?string $website = null,
        public ?string $addressLine1 = null,
        public ?string $addressLine2 = null,
        public ?string $city = null,
        public ?string $postalCode = null,
        public ?string $country = null,
        public ?bool $isActive = null,
    ) {}
}
