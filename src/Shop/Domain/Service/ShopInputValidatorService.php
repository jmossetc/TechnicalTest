<?php

namespace Mossetc\TechnicalTest\Shop\Domain\Service;

use InvalidArgumentException;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Mossetc\TechnicalTest\Shop\Application\DTO\ShopInput;

final readonly class ShopInputValidatorService
{
    public function __construct(private PhoneNumberUtil $phoneNumberUtil) {}

    /**
     * @param array<string, mixed> $inputs
     * @throws InvalidArgumentException for any validation or lookup failure (→ 422)
     */
    public function validate(array $inputs): ShopInput
    {
        $name = $this->nullableString($inputs['name'] ?? '');
        $email = $this->nullableString($inputs['email'] ?? '');
        $phoneNumber = $this->nullableString($inputs['phone_number'] ?? '');
        $addressLine1 = $this->nullableString($inputs['addressLine1'] ?? '');
        $addressLine2 = $this->nullableString($inputs['addressLine2'] ?? '');
        $city = $this->nullableString($inputs['city'] ?? '');
        $postalCode = $this->nullableString($inputs['postal_code'] ?? '');
        $country = $this->nullableString($inputs['country'] ?? '');
        $latitude = $this->checkCoordinate($inputs['latitude'] ?? null, 90, 'Latitude');
        $longitude = $this->checkCoordinate($inputs['longitude'] ?? null, 180, 'Longitude');
        $isDigital = (bool)($inputs['is_digital'] ?? false);

        if ($name === null) {
            throw new InvalidArgumentException('name is required');
        }

        if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('email is not valid');
        }

        if ($phoneNumber !== null) {
            try {
                $parsed = $this->phoneNumberUtil->parse($phoneNumber, 'FR');
                $phoneNumber = $this->phoneNumberUtil->format($parsed, PhoneNumberFormat::E164);
            } catch (NumberParseException) {
                throw new InvalidArgumentException('Invalid phone number format');
            }
        }

        return new ShopInput(
            $name,
            $email,
            $phoneNumber,
            $addressLine1,
            $addressLine2,
            $city,
            $postalCode,
            $country,
            $latitude,
            $longitude,
            $isDigital,
        );
    }

    private function checkCoordinate(mixed $coordinate, int $boundary, string $coordinateName): ?float
    {
        if (!empty($coordinate)) {
            if (!is_numeric($coordinate)) {
                throw new InvalidArgumentException($coordinateName . ' must be numeric');
            }
            $coordinate = (float)$coordinate;
            if ($coordinate < -$boundary || $coordinate > $boundary) {
                throw new InvalidArgumentException($coordinateName . ' must be between -' . $boundary . ' and ' . $boundary);
            }
        } else {
            $coordinate = null;
        }

        return $coordinate;
    }

    private function nullableString(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }
        return $value !== '' ? $value : null;
    }
}