<?php

namespace Mossetc\TechnicalTest\Company\Domain\Service;

use InvalidArgumentException;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Mossetc\TechnicalTest\Company\Application\DTO\CompanyInput;

final readonly class CompanyInputValidatorService
{
    public function __construct(private PhoneNumberUtil $phoneNumberUtil){}

    /**
     * @param array<string, mixed> $inputs
     * @throws InvalidArgumentException for any validation or lookup failure (→ 422)
     */
    public function validate(array $inputs): CompanyInput
    {
        $name = $this->nullableString($inputs['name'] ?? '');
        $email = $this->nullableString($inputs['email'] ?? '');
        $phoneNumber = $this->nullableString($inputs['phone_number'] ?? '');
        $website = $this->nullableString($inputs['website'] ?? '');
        $addressLine1 = $this->nullableString($inputs['addressLine1'] ?? '');
        $addressLine2 = $this->nullableString($inputs['addressLine2'] ?? '');
        $city = $this->nullableString($inputs['city'] ?? '');
        $postalCode = $this->nullableString($inputs['postal_code'] ?? '');
        $country = $this->nullableString($inputs['country'] ?? '');

        if ($name === null) {
            throw new InvalidArgumentException('name is required');
        }

        if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('email is not valid');
        }

        if ($phoneNumber !== null) {
            try {
                $parsed = $this->phoneNumberUtil->parse($phoneNumber, 'FR');
                $phoneNumber = $this->phoneNumberUtil->format($parsed, PhoneNumberFormat::INTERNATIONAL);
            } catch (NumberParseException) {
                throw new InvalidArgumentException('Invalid phone number format');
            }
        }

        return new CompanyInput(
            name: $name,
            email: $email,
            phoneNumber: $phoneNumber,
            website: $website,
            addressLine1: $addressLine1,
            addressLine2: $addressLine2,
            city: $city,
            postalCode: $postalCode,
            country: $country,
        );
    }

    private function nullableString(string $value): ?string
    {
        return $value !== '' ? $value : null;
    }
}