<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Company\Application\Command;

use Mossetc\TechnicalTest\Company\Application\DTO\CompanyInput;

final readonly class UpdateCompany
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $email = null,
        public ?string $phoneNumber = null,
        public ?string $website = null,
        public ?string $addressLine1 = null,
        public ?string $addressLine2 = null,
        public ?string $city = null,
        public ?string $postalCode = null,
        public ?string $country = null,
        public ?bool $isActive = null,
    ) {}

    public static function fromCompanyInput(CompanyInput $input, string $id, bool $isActive): self
    {
        return new self(
            $id,
            $input->name,
            $input->email,
            $input->phoneNumber,
            $input->website,
            $input->addressLine1,
            $input->addressLine2,
            $input->city,
            $input->postalCode,
            $input->country,
            $isActive,
        );
    }
}
