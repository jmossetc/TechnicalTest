<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Shop\Application\Command;

final readonly class CreateShop
{
    public function __construct(
        public string $companyId,
        public string $name,
        public ?string $email = null,
        public ?string $phoneNumber = null,
        public ?string $addressLine1 = null,
        public ?string $addressLine2 = null,
        public ?string $city = null,
        public ?string $postalCode = null,
        public ?string $country = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public bool $isDigital = false,
    ) {}
}
