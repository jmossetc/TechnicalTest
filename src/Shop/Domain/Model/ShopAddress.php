<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Shop\Domain\Model;

final readonly class ShopAddress
{
    public function __construct(
        public ?string $addressLine1 = null,
        public ?string $addressLine2 = null,
        public ?string $city = null,
        public ?string $postalCode = null,
        public ?string $country = null,
    ) {}
}
