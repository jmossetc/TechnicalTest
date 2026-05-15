<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Company\Domain\Model;

use DateTimeImmutable;

final readonly class Company implements \JsonSerializable
{
    public function __construct(
        public CompanyId $id,
        public CompanyName $name,
        public ?string $email = null,
        public ?string $phoneNumber = null,
        public ?string $website = null,
        public ?string $addressLine1 = null,
        public ?string $addressLine2 = null,
        public ?string $city = null,
        public ?string $postalCode = null,
        public ?string $country = null,
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
            'name'          => $this->name->value,
            'email'         => $this->email,
            'phone_number'  => $this->phoneNumber,
            'website'       => $this->website,
            'address_line_1' => $this->addressLine1,
            'address_line_2' => $this->addressLine2,
            'city'          => $this->city,
            'postal_code'   => $this->postalCode,
            'country'       => $this->country,
            'is_active'     => $this->isActive,
            'created_at'    => $this->createdAt->format('Y-m-d\TH:i:s\Z'),
            'updated_at'    => $this->updatedAt?->format('Y-m-d\TH:i:s\Z'),
        ];
    }
}
