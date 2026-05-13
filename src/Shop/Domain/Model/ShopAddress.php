<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Shop\Domain\Model;

final readonly class ShopAddress
{
    public function __construct(
        public ?string $street = null,
        public ?string $city = null,
        public ?string $zip = null,
        public ?string $country = null,
    ) {}
}
