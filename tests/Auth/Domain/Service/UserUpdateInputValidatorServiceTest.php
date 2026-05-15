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

        $this->assertNull($input->firstName);
        $this->assertNull($input->lastName);
        $this->assertNull($input->email);
        $this->assertNull($input->phoneNumber);
        $this->assertNull($input->password);
        $this->assertNull($input->currentPassword);
        $this->assertNull($input->isActive);
        $this->assertNull($input->role);
        $this->assertNull($input->companyId);
        $this->assertNull($input->shopId);
    }

    public function testAcceptsValidPartialInput(): void
    {
        $input = $this->validator()->validate(['first_name' => 'Jane', 'last_name' => 'Doe']);

        $this->assertSame('Jane', $input->firstName);
        $this->assertSame('Doe', $input->lastName);
        $this->assertNull($input->email);
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

        $this->assertSame('+33612345678', $input->phoneNumber);
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

        $this->assertFalse($input->isActive);
    }

    public function testIgnoresNonBoolIsActive(): void
    {
        $input = $this->validator()->validate(['is_active' => 'yes']);

        $this->assertNull($input->isActive);
    }

    public function testCurrentPasswordPassedThrough(): void
    {
        $input = $this->validator()->validate(['current_password' => 'myOldPass1']);

        $this->assertSame('myOldPass1', $input->currentPassword);
    }
}
