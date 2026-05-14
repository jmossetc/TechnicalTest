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
     * @param array<string, mixed> $requestBody
     * @throws InvalidArgumentException for any validation or lookup failure (→ 422)
     */
    public function validate(array $requestBody): RegistrationInput
    {
        $email = is_string($requestBody['email'] ?? null) ? $requestBody['email'] : null;
        $password = is_string($requestBody['password'] ?? null) ? $requestBody['password'] : null;
        $firstName = is_string($requestBody['first_name'] ?? null) ? $requestBody['first_name'] : null;
        $lastName = is_string($requestBody['last_name'] ?? null) ? $requestBody['last_name'] : null;
        $roleStr = is_string($requestBody['role'] ?? null) ? $requestBody['role'] : null;
        $companyId = is_string($requestBody['company_id'] ?? null) ? $requestBody['company_id'] : null;
        $shopId = is_string($requestBody['shop_id'] ?? null) ? $requestBody['shop_id'] : null;
        $phoneNumber = is_string($requestBody['phone_number'] ?? null) ? $requestBody['phone_number'] : null;

        if ($email === null || $password === null || $firstName === null || $lastName === null) {
            throw new InvalidArgumentException('email, password, first_name and last_name are required');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email format');
        }

        try {
            $phoneNumber = $this->phoneNumberUtil->parse($phoneNumber, 'FR');
            $phoneNumber = $this->phoneNumberUtil->format($phoneNumber, PhoneNumberFormat::INTERNATIONAL);
        } catch (NumberParseException) {
            throw new InvalidArgumentException('Invalid phone number format');
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

    public function checkRole(?string $roleStr, ?string $companyId, ?string $shopId): Role
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
}
