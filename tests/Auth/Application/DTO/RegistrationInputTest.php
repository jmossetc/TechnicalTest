<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Auth\Application\DTO;

use Mossetc\TechnicalTest\Auth\Application\DTO\RegistrationInput;
use Mossetc\TechnicalTest\Auth\Domain\Model\Role;
use PHPUnit\Framework\TestCase;

final class RegistrationInputTest extends TestCase
{
    public function testHoldsAllFields(): void
    {
        $input = new RegistrationInput(
            role:            Role::CompanyAdmin,
            companyId:       '11111111-1111-4111-8111-111111111111',
            shopId:          null,
            phoneNumber:     '+33612345678',
            firstName:       'Alice',
            lastName:        'Smith',
            email:           'alice@example.com',
            password:        'secret123',
        );

        $this->assertSame(Role::CompanyAdmin,                   $input->role);
        $this->assertSame('11111111-1111-4111-8111-111111111111', $input->companyId);
        $this->assertNull($input->shopId);
        $this->assertSame('+33612345678',                        $input->phoneNumber);
        $this->assertSame('Alice',                               $input->firstName);
        $this->assertSame('Smith',                               $input->lastName);
        $this->assertSame('alice@example.com',                   $input->email);
        $this->assertSame('secret123',                           $input->password);
    }

    public function testAllOptionalFieldsCanBeNull(): void
    {
        $input = new RegistrationInput(
            role:            Role::Employee,
            companyId:       null,
            shopId:          null,
            phoneNumber:     null,
            firstName:       'Bob',
            lastName:        'Jones',
            email:           'bob@example.com',
            password:        'pass1234',
        );

        $this->assertNull($input->companyId);
        $this->assertNull($input->shopId);
        $this->assertNull($input->phoneNumber);
    }

    public function testWorksWithShopManagerRole(): void
    {
        $input = new RegistrationInput(
            role:            Role::ShopManager,
            companyId:       '11111111-1111-4111-8111-111111111111',
            shopId:          'aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
            phoneNumber:     null,
            firstName:       'Sam',
            lastName:        'Manager',
            email:           'sm@example.com',
            password:        'pass1234',
        );

        $this->assertSame(Role::ShopManager,                     $input->role);
        $this->assertSame('aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa', $input->shopId);
    }

    public function testWorksWithAdminRole(): void
    {
        $input = new RegistrationInput(
            role:            Role::Admin,
            companyId:       null,
            shopId:          null,
            phoneNumber:     null,
            firstName:       'Admin',
            lastName:        'User',
            email:           'admin@example.com',
            password:        'pass1234',
        );

        $this->assertSame(Role::Admin, $input->role);
    }
}
