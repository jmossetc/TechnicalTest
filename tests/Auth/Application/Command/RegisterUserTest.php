<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Auth\Application\Command;

use Mossetc\TechnicalTest\Auth\Application\Command\RegisterUser;
use Mossetc\TechnicalTest\Auth\Application\DTO\RegistrationInput;
use Mossetc\TechnicalTest\Auth\Domain\Model\Role;
use PHPUnit\Framework\TestCase;

final class RegisterUserTest extends TestCase
{
    // ── Constructor ───────────────────────────────────────────────────────────

    public function testConstructorStoresAllFields(): void
    {
        $cmd = new RegisterUser(
            email:       'alice@example.com',
            password:    'password123',
            firstName:   'Alice',
            lastName:    'Smith',
            role:        'company_admin',
            companyId:   '11111111-1111-4111-8111-111111111111',
            shopId:      null,
            phoneNumber: '+33612345678',
        );

        $this->assertSame('alice@example.com',                   $cmd->email);
        $this->assertSame('password123',                         $cmd->password);
        $this->assertSame('Alice',                               $cmd->firstName);
        $this->assertSame('Smith',                               $cmd->lastName);
        $this->assertSame('company_admin',                       $cmd->role);
        $this->assertSame('11111111-1111-4111-8111-111111111111', $cmd->companyId);
        $this->assertNull($cmd->shopId);
        $this->assertSame('+33612345678',                        $cmd->phoneNumber);
    }

    public function testRoleDefaultsToEmployee(): void
    {
        $cmd = new RegisterUser('a@b.com', 'password123', 'A', 'B');

        $this->assertSame('employee', $cmd->role);
    }

    public function testOptionalFieldsDefaultToNull(): void
    {
        $cmd = new RegisterUser('a@b.com', 'password123', 'A', 'B');

        $this->assertNull($cmd->companyId);
        $this->assertNull($cmd->shopId);
        $this->assertNull($cmd->phoneNumber);
    }

    // ── fromRegistrationInput() ───────────────────────────────────────────────

    public function testFromRegistrationInputMapsAllFields(): void
    {
        $input = new RegistrationInput(
            role:            Role::CompanyAdmin,
            companyId:       '11111111-1111-4111-8111-111111111111',
            shopId:          null,
            phoneNumber:     '+33612345678',
            firstName:       'Alice',
            lastName:        'Smith',
            email:           'alice@example.com',
            password:        'password123',
        );

        $cmd = RegisterUser::fromRegistrationInput($input);

        $this->assertSame('alice@example.com',                   $cmd->email);
        $this->assertSame('password123',                         $cmd->password);
        $this->assertSame('Alice',                               $cmd->firstName);
        $this->assertSame('Smith',                               $cmd->lastName);
        $this->assertSame('company_admin',                       $cmd->role);
        $this->assertSame('11111111-1111-4111-8111-111111111111', $cmd->companyId);
        $this->assertNull($cmd->shopId);
        $this->assertSame('+33612345678',                        $cmd->phoneNumber);
    }

    public function testFromRegistrationInputWithShopManager(): void
    {
        $input = new RegistrationInput(
            role:            Role::ShopManager,
            companyId:       '11111111-1111-4111-8111-111111111111',
            shopId:          'aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
            phoneNumber:     null,
            firstName:       'Sam',
            lastName:        'Manager',
            email:           'sm@example.com',
            password:        'password123',
        );

        $cmd = RegisterUser::fromRegistrationInput($input);

        $this->assertSame('shop_manager',                        $cmd->role);
        $this->assertSame('aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa', $cmd->shopId);
        $this->assertSame('11111111-1111-4111-8111-111111111111', $cmd->companyId);
    }

    public function testFromRegistrationInputWithEmployee(): void
    {
        $input = new RegistrationInput(
            role:            Role::Employee,
            companyId:       null,
            shopId:          null,
            phoneNumber:     null,
            firstName:       'Bob',
            lastName:        'Jones',
            email:           'bob@example.com',
            password:        'password123',
        );

        $cmd = RegisterUser::fromRegistrationInput($input);

        $this->assertSame('employee', $cmd->role);
        $this->assertNull($cmd->companyId);
        $this->assertNull($cmd->shopId);
        $this->assertNull($cmd->phoneNumber);
    }

    public function testFromRegistrationInputWithAdmin(): void
    {
        $input = new RegistrationInput(
            role:            Role::Admin,
            companyId:       null,
            shopId:          null,
            phoneNumber:     null,
            firstName:       'Admin',
            lastName:        'User',
            email:           'admin@example.com',
            password:        'password123',
        );

        $cmd = RegisterUser::fromRegistrationInput($input);

        $this->assertSame('admin', $cmd->role);
    }
}
