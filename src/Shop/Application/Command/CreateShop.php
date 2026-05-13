<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Shop\Application\Command;

final readonly class CreateShop
{
    public function __construct(
        public string $companyId,
        public string $name,
        public ?string $street = null,
        public ?string $city = null,
        public ?string $zip = null,
        public ?string $country = null,
    ) {}
}
