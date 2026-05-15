<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Domain\Service;

use InvalidArgumentException;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Mossetc\TechnicalTest\Auth\Application\DTO\UserUpdateInput;

final readonly class UserUpdateInputValidatorService
{
    public function __construct(private PhoneNumberUtil $phoneNumberUtil) {}

    /**
     * Parse and validate raw PATCH body fields. All fields are optional.
     *
     * @param array<string, mixed> $body
     * @throws InvalidArgumentException
     */
    public function validate(array $body): UserUpdateInput
    {
        $firstName   = $this->nullableString($body['first_name'] ?? null);
        $lastName    = $this->nullableString($body['last_name'] ?? null);
        $email       = $this->nullableString($body['email'] ?? null);
        $phoneNumber = $this->nullableString($body['phone_number'] ?? null);
        $password    = $this->nullableString($body['password'] ?? null);
        $current     = $this->nullableString($body['current_password'] ?? null);
        $isActive    = isset($body['is_active']) && \is_bool($body['is_active']) ? $body['is_active'] : null;
        $role        = $this->nullableString($body['role'] ?? null);
        $companyId   = $this->nullableString($body['company_id'] ?? null);
        $shopId      = $this->nullableString($body['shop_id'] ?? null);

        if ($firstName !== null && trim($firstName) === '') {
            throw new InvalidArgumentException('first_name cannot be empty');
        }

        if ($lastName !== null && trim($lastName) === '') {
            throw new InvalidArgumentException('last_name cannot be empty');
        }

        if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email format');
        }

        if ($phoneNumber !== null) {
            try {
                $parsed      = $this->phoneNumberUtil->parse($phoneNumber, 'FR');
                $phoneNumber = $this->phoneNumberUtil->format($parsed, PhoneNumberFormat::E164);
            } catch (NumberParseException) {
                throw new InvalidArgumentException('Invalid phone number format');
            }
        }

        if ($password !== null && \strlen($password) < 8) {
            throw new InvalidArgumentException('Password must be at least 8 characters long');
        }

        return new UserUpdateInput(
            firstName: $firstName,
            lastName: $lastName,
            email: $email,
            phoneNumber: $phoneNumber,
            password: $password,
            currentPassword: $current,
            isActive: $isActive,
            role: $role,
            companyId: $companyId,
            shopId: $shopId,
        );
    }

    private function nullableString(mixed $value): ?string
    {
        return \is_string($value) && $value !== '' ? $value : null;
    }
}
