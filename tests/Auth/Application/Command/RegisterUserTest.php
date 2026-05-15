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
            email: 'alice@example.com',
            password: 'password123',
            firstName: 'Alice',
            lastName: 'Smith',
            role: 'company_admin',
            companyId: '11111111-1111-4111-8111-111111111111',
            shopId: null,
            phoneNumber: '+33612345678',
        );

        self::assertSame('alice@example.com', $cmd->email);
        self::assertSame('password123', $cmd->password);
        self::assertSame('Alice', $cmd->firstName);
        self::assertSame('Smith', $cmd->lastName);
        self::assertSame('company_admin', $cmd->role);
        self::assertSame('11111111-1111-4111-8111-111111111111', $cmd->companyId);
        self::assertNull($cmd->shopId);
        self::assertSame('+33612345678', $cmd->phoneNumber);
    }

    public function testRoleDefaultsToEmployee(): void
    {
        $cmd = new RegisterUser('a@b.com', 'password123', 'A', 'B');

        self::assertSame('employee', $cmd->role);
    }

    public function testOptionalFieldsDefaultToNull(): void
    {
        $cmd = new RegisterUser('a@b.com', 'password123', 'A', 'B');

        self::assertNull($cmd->companyId);
        self::assertNull($cmd->shopId);
        self::assertNull($cmd->phoneNumber);
    }

    // ── fromRegistrationInput() ───────────────────────────────────────────────

    public function testFromRegistrationInputMapsAllFields(): void
    {
        $input = new RegistrationInput(
            role: Role::CompanyAdmin,
            companyId: '11111111-1111-4111-8111-111111111111',
            shopId: null,
            phoneNumber: '+33612345678',
            firstName: 'Alice',
            lastName: 'Smith',
            email: 'alice@example.com',
            password: 'password123',
        );

        $cmd = RegisterUser::fromRegistrationInput($input);

        self::assertSame('alice@example.com', $cmd->email);
        self::assertSame('password123', $cmd->password);
        self::assertSame('Alice', $cmd->firstName);
        self::assertSame('Smith', $cmd->lastName);
        self::assertSame('company_admin', $cmd->role);
        self::assertSame('11111111-1111-4111-8111-111111111111', $cmd->companyId);
        self::assertNull($cmd->shopId);
        self::assertSame('+33612345678', $cmd->phoneNumber);
    }

    public function testFromRegistrationInputWithShopManager(): void
    {
        $input = new RegistrationInput(
            role: Role::ShopManager,
            companyId: '11111111-1111-4111-8111-111111111111',
            shopId: 'aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
            phoneNumber: null,
            firstName: 'Sam',
            lastName: 'Manager',
            email: 'sm@example.com',
            password: 'password123',
        );

        $cmd = RegisterUser::fromRegistrationInput($input);

        self::assertSame('shop_manager', $cmd->role);
        self::assertSame('aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa', $cmd->shopId);
        self::assertSame('11111111-1111-4111-8111-111111111111', $cmd->companyId);
    }

    public function testFromRegistrationInputWithEmployee(): void
    {
        $input = new RegistrationInput(
            role: Role::Employee,
            companyId: null,
            shopId: null,
            phoneNumber: null,
            firstName: 'Bob',
            lastName: 'Jones',
            email: 'bob@example.com',
            password: 'password123',
        );

        $cmd = RegisterUser::fromRegistrationInput($input);

        self::assertSame('employee', $cmd->role);
        self::assertNull($cmd->companyId);
        self::assertNull($cmd->shopId);
        self::assertNull($cmd->phoneNumber);
    }

    public function testFromRegistrationInputWithAdmin(): void
    {
        $input = new RegistrationInput(
            role: Role::Admin,
            companyId: null,
            shopId: null,
            phoneNumber: null,
            firstName: 'Admin',
            lastName: 'User',
            email: 'admin@example.com',
            password: 'password123',
        );

        $cmd = RegisterUser::fromRegistrationInput($input);

        self::assertSame('admin', $cmd->role);
    }
}
