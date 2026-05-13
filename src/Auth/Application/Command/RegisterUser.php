<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Application\Command;

use Mossetc\TechnicalTest\Auth\Application\DTO\RegistrationInput;

final readonly class RegisterUser
{
    public function __construct(
        public string $email,
        public string $password,
        public string $firstName,
        public string $lastName,
        public string $role = 'employee',
        public ?string $companyId = null,
        public ?string $shopId = null,
        public ?string $phoneNumber = null,
    ) {}

    public static function fromRegistrationInput(RegistrationInput $input): self
    {
        return new self(
            email:       $input->email,
            password:    $input->password,
            firstName:   $input->firstName,
            lastName:    $input->lastName,
            role:        $input->role->value,
            companyId:   $input->companyId,
            shopId:      $input->shopId,
            phoneNumber: $input->phoneNumber,
        );
    }
}
