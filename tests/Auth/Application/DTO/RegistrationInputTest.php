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
            role: Role::CompanyAdmin,
            companyId: '11111111-1111-4111-8111-111111111111',
            shopId: null,
            phoneNumber: '+33612345678',
            firstName: 'Alice',
            lastName: 'Smith',
            email: 'alice@example.com',
            password: 'secret123',
        );

        self::assertSame(Role::CompanyAdmin, $input->role);
        self::assertSame('11111111-1111-4111-8111-111111111111', $input->companyId);
        self::assertNull($input->shopId);
        self::assertSame('+33612345678', $input->phoneNumber);
        self::assertSame('Alice', $input->firstName);
        self::assertSame('Smith', $input->lastName);
        self::assertSame('alice@example.com', $input->email);
        self::assertSame('secret123', $input->password);
    }

    public function testAllOptionalFieldsCanBeNull(): void
    {
        $input = new RegistrationInput(
            role: Role::Employee,
            companyId: null,
            shopId: null,
            phoneNumber: null,
            firstName: 'Bob',
            lastName: 'Jones',
            email: 'bob@example.com',
            password: 'pass1234',
        );

        self::assertNull($input->companyId);
        self::assertNull($input->shopId);
        self::assertNull($input->phoneNumber);
    }

    public function testWorksWithShopManagerRole(): void
    {
        $input = new RegistrationInput(
            role: Role::ShopManager,
            companyId: '11111111-1111-4111-8111-111111111111',
            shopId: 'aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
            phoneNumber: null,
            firstName: 'Sam',
            lastName: 'Manager',
            email: 'sm@example.com',
            password: 'pass1234',
        );

        self::assertSame(Role::ShopManager, $input->role);
        self::assertSame('aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa', $input->shopId);
    }

    public function testWorksWithAdminRole(): void
    {
        $input = new RegistrationInput(
            role: Role::Admin,
            companyId: null,
            shopId: null,
            phoneNumber: null,
            firstName: 'Admin',
            lastName: 'User',
            email: 'admin@example.com',
            password: 'pass1234',
        );

        self::assertSame(Role::Admin, $input->role);
    }
}
