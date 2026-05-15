<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Company\Domain;

use DateTimeImmutable;
use Mossetc\TechnicalTest\Company\Domain\Model\Company;
use Mossetc\TechnicalTest\Company\Domain\Model\CompanyId;
use Mossetc\TechnicalTest\Company\Domain\Model\CompanyName;
use PHPUnit\Framework\TestCase;

final class CompanyTest extends TestCase
{
    /** @param array{id?: CompanyId, name?: CompanyName} $overrides */
    private function makeCompany(array $overrides = []): Company
    {
        return new Company(
            id: $overrides['id']   ?? CompanyId::generate(),
            name: $overrides['name'] ?? new CompanyName('Acme Corp'),
        );
    }

    public function testHoldsRequiredFields(): void
    {
        $id      = CompanyId::generate();
        $name    = new CompanyName('Globex');
        $company = new Company(id: $id, name: $name);

        self::assertTrue($id->equals($company->id));
        self::assertTrue($name->equals($company->name));
    }

    public function testOptionalFieldsDefaultToNull(): void
    {
        $company = $this->makeCompany();

        self::assertNull($company->email);
        self::assertNull($company->phoneNumber);
        self::assertNull($company->website);
        self::assertNull($company->addressLine1);
        self::assertNull($company->addressLine2);
        self::assertNull($company->city);
        self::assertNull($company->postalCode);
        self::assertNull($company->country);
        self::assertNull($company->deletedAt);
    }

    public function testIsActiveDefaultsToTrue(): void
    {
        self::assertTrue($this->makeCompany()->isActive);
    }

    public function testAcceptsAllOptionalFields(): void
    {
        $company = new Company(
            id: CompanyId::generate(),
            name: new CompanyName('Test'),
            email: 'contact@test.com',
            phoneNumber: '+1234567890',
            website: 'https://test.com',
            addressLine1: '1 Main Street',
            addressLine2: 'Suite 100',
            city: 'Paris',
            postalCode: '75001',
            country: 'France',
            isActive: false,
        );

        self::assertSame('contact@test.com', $company->email);
        self::assertSame('+1234567890', $company->phoneNumber);
        self::assertSame('https://test.com', $company->website);
        self::assertSame('1 Main Street', $company->addressLine1);
        self::assertSame('Suite 100', $company->addressLine2);
        self::assertSame('Paris', $company->city);
        self::assertSame('75001', $company->postalCode);
        self::assertSame('France', $company->country);
        self::assertFalse($company->isActive);
    }

    public function testAcceptsExplicitTimestamps(): void
    {
        $created = new DateTimeImmutable('2024-01-01 10:00:00');
        $deleted = new DateTimeImmutable('2024-06-01 09:00:00');

        $company = new Company(
            id: CompanyId::generate(),
            name: new CompanyName('Test'),
            createdAt: $created,
            deletedAt: $deleted,
        );

        self::assertSame($created, $company->createdAt);
        self::assertSame($deleted, $company->deletedAt);
    }
}
