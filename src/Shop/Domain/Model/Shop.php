<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Shop\Domain\Model;

use DateTimeImmutable;
use Mossetc\TechnicalTest\Company\Domain\Model\CompanyId;

final readonly class Shop implements \JsonSerializable
{
    public function __construct(
        public ShopId $id,
        public CompanyId $companyId,
        public ShopName $name,
        public ShopAddress $address = new ShopAddress(),
        public ?string $email = null,
        public ?string $phoneNumber = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public bool $isDigital = false,
        public bool $isActive = true,
        public DateTimeImmutable $createdAt = new DateTimeImmutable(),
        public ?DateTimeImmutable $updatedAt = null,
        public ?DateTimeImmutable $deletedAt = null,
    ) {}

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'id'            => $this->id->value,
            'company_id'    => $this->companyId->value,
            'name'          => $this->name->value,
            'email'         => $this->email,
            'phone_number'  => $this->phoneNumber,
            'address_line_1' => $this->address->addressLine1,
            'address_line_2' => $this->address->addressLine2,
            'city'          => $this->address->city,
            'postal_code'   => $this->address->postalCode,
            'country'       => $this->address->country,
            'latitude'      => $this->latitude,
            'longitude'     => $this->longitude,
            'is_digital'    => $this->isDigital,
            'is_active'     => $this->isActive,
            'created_at'    => $this->createdAt->format('Y-m-d\TH:i:s\Z'),
            'updated_at'    => $this->updatedAt?->format('Y-m-d\TH:i:s\Z'),
        ];
    }
}
