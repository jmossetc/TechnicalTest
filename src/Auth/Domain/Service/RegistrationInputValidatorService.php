<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Domain\Service;

use InvalidArgumentException;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Mossetc\TechnicalTest\Auth\Application\DTO\RegistrationInput;
use Mossetc\TechnicalTest\Auth\Domain\Model\Role;
use Mossetc\TechnicalTest\Shop\Domain\Model\ShopId;
use Mossetc\TechnicalTest\Shop\Domain\Repository\ShopRepositoryInterface;

final readonly class RegistrationInputValidatorService
{
    public function __construct(
        private ShopRepositoryInterface $shopRepository,
        private PhoneNumberUtil $phoneNumberUtil,
    ) {}

    /**
     * Parse and validate the raw registration request fields.
     *
     * @param array<string, mixed> $inputs
     * @throws InvalidArgumentException for any validation or lookup failure (→ 422)
     */
    public function validate(array $inputs): RegistrationInput
    {
        $email = $this->nullableString($inputs['email'] ?? '');
        $password = $this->nullableString($inputs['password'] ?? '');
        $firstName = $this->nullableString($inputs['first_name'] ?? '');
        $lastName = $this->nullableString($inputs['last_name'] ?? '');
        $roleStr = $this->nullableString($inputs['role'] ?? '');
        $companyId = $this->nullableString($inputs['company_id'] ?? '');
        $shopId = $this->nullableString($inputs['shop_id'] ?? '');
        $phoneNumber = $this->nullableString($inputs['phone_number'] ?? '');

        if ($email === null || $password === null || $firstName === null || $lastName === null) {
            throw new InvalidArgumentException('email, password, first_name and last_name are required');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email format');
        }

        if ($phoneNumber !== null) {
            try {
                $parsed = $this->phoneNumberUtil->parse($phoneNumber, 'FR');
                $phoneNumber = $this->phoneNumberUtil->format($parsed, PhoneNumberFormat::INTERNATIONAL);
            } catch (NumberParseException) {
                throw new InvalidArgumentException('Invalid phone number format');
            }
        }

        $role = $this->checkRole($roleStr, $companyId, $shopId);

        if ($role === Role::ShopManager && $shopId !== null) {
            try {
                $shopUuid = new ShopId($shopId);
            } catch (InvalidArgumentException) {
                throw new InvalidArgumentException('Invalid shop_id format');
            }

            $shop = $this->shopRepository->findById($shopUuid);
            if ($shop === null) {
                throw new InvalidArgumentException('Shop not found');
            }

            $companyId = $shop->companyId->value;
        }

        return new RegistrationInput($role, $companyId, $shopId, $phoneNumber, $firstName, $lastName, $email, $password,);
    }

    private function checkRole(?string $roleStr, ?string $companyId, ?string $shopId): Role
    {
        if (empty($roleStr)) {
            throw new InvalidArgumentException('role is required');
        }

        $role = Role::tryFrom($roleStr);
        if ($role === null) {
            throw new InvalidArgumentException("Invalid role: {$roleStr}");
        }

        if ($role === Role::CompanyAdmin && $companyId === null) {
            throw new InvalidArgumentException('company_id is required for company_admin role');
        }

        if ($role === Role::ShopManager && $shopId === null) {
            throw new InvalidArgumentException('shop_id is required for shop_manager role');
        }

        return $role;
    }

    private function nullableString(string $value): ?string
    {
        return $value  !== '' ? $value : null;
    }
}
