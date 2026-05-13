<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Shop\Application\Command;

final readonly class UpdateShop
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $street = null,
        public ?string $city = null,
        public ?string $zip = null,
        public ?string $country = null,
    ) {}
}
