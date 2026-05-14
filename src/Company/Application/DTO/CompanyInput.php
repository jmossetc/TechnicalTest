<?php

namespace Mossetc\TechnicalTest\Company\Application\DTO;

final readonly class CompanyInput
{
    public function __construct(
        public string $name,
        public ?string $email = null,
        public ?string $phoneNumber = null,
        public ?string $website = null,
        public ?string $addressLine1 = null,
        public ?string $addressLine2 = null,
        public ?string $city = null,
        public ?string $postalCode = null,
        public ?string $country = null,
    ) {}
}