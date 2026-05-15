<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Auth\Domain\Service;

use InvalidArgumentException;
use libphonenumber\PhoneNumberUtil;
use Mossetc\TechnicalTest\Auth\Domain\Service\UserUpdateInputValidatorService;
use PHPUnit\Framework\TestCase;

final class UserUpdateInputValidatorServiceTest extends TestCase
{
    private function validator(): UserUpdateInputValidatorService
    {
        return new UserUpdateInputValidatorService(PhoneNumberUtil::getInstance());
    }

    public function testEmptyBodyReturnsAllNulls(): void
    {
        $input = $this->validator()->validate([]);

        self::assertNull($input->firstName);
        self::assertNull($input->lastName);
        self::assertNull($input->email);
        self::assertNull($input->phoneNumber);
        self::assertNull($input->password);
        self::assertNull($input->currentPassword);
        self::assertNull($input->isActive);
        self::assertNull($input->role);
        self::assertNull($input->companyId);
        self::assertNull($input->shopId);
    }

    public function testAcceptsValidPartialInput(): void
    {
        $input = $this->validator()->validate(['first_name' => 'Jane', 'last_name' => 'Doe']);

        self::assertSame('Jane', $input->firstName);
        self::assertSame('Doe', $input->lastName);
        self::assertNull($input->email);
    }

    public function testRejectsInvalidEmail(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email format');

        $this->validator()->validate(['email' => 'not-an-email']);
    }

    public function testRejectsInvalidPhoneNumber(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid phone number format');

        $this->validator()->validate(['phone_number' => 'not-a-phone']);
    }

    public function testNormalizesPhoneToE164(): void
    {
        $input = $this->validator()->validate(['phone_number' => '+33612345678']);

        self::assertSame('+33612345678', $input->phoneNumber);
    }

    public function testRejectsBlankFirstName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('first_name cannot be empty');

        $this->validator()->validate(['first_name' => '   ']);
    }

    public function testRejectsBlankLastName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('last_name cannot be empty');

        $this->validator()->validate(['last_name' => '   ']);
    }

    public function testRejectsShortPassword(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Password must be at least 8 characters long');

        $this->validator()->validate(['password' => 'short']);
    }

    public function testAcceptsIsActiveBool(): void
    {
        $input = $this->validator()->validate(['is_active' => false]);

        self::assertFalse($input->isActive);
    }

    public function testIgnoresNonBoolIsActive(): void
    {
        $input = $this->validator()->validate(['is_active' => 'yes']);

        self::assertNull($input->isActive);
    }

    public function testCurrentPasswordPassedThrough(): void
    {
        $input = $this->validator()->validate(['current_password' => 'myOldPass1']);

        self::assertSame('myOldPass1', $input->currentPassword);
    }
}
