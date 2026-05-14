<?php

namespace Mossetc\TechnicalTest\Shop\Application\DTO;

final readonly class ShopInput
{
    public function __construct(
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