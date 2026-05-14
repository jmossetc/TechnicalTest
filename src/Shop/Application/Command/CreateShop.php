<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Shop\Application\Command;

use Mossetc\TechnicalTest\Shop\Application\DTO\ShopInput;

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

    public static function fromShopInput(ShopInput $input, string $companyId): self
    {
        return new self(
            $companyId,
            $input->name,
            $input->email,
            $input->phoneNumber,
            $input->addressLine1,
            $input->addressLine2,
            $input->city,
            $input->postalCode,
            $input->country,
            $input->latitude,
            $input->longitude,
            $input->isDigital,
        );
    }
}
