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
            id:   $overrides['id']   ?? CompanyId::generate(),
            name: $overrides['name'] ?? new CompanyName('Acme Corp'),
        );
    }

    public function testHoldsRequiredFields(): void
    {
        $id      = CompanyId::generate();
        $name    = new CompanyName('Globex');
        $company = new Company(id: $id, name: $name);

        $this->assertTrue($id->equals($company->id));
        $this->assertTrue($name->equals($company->name));
    }

    public function testOptionalFieldsDefaultToNull(): void
    {
        $company = $this->makeCompany();

        $this->assertNull($company->email);
        $this->assertNull($company->phoneNumber);
        $this->assertNull($company->website);
        $this->assertNull($company->addressLine1);
        $this->assertNull($company->addressLine2);
        $this->assertNull($company->city);
        $this->assertNull($company->postalCode);
        $this->assertNull($company->country);
        $this->assertNull($company->deletedAt);
    }

    public function testIsActiveDefaultsToTrue(): void
    {
        $this->assertTrue($this->makeCompany()->isActive);
    }

    public function testTimestampsDefaultToNow(): void
    {
        $before  = new DateTimeImmutable();
        $company = $this->makeCompany();
        $after   = new DateTimeImmutable();

        $this->assertGreaterThanOrEqual($before, $company->createdAt);
        $this->assertLessThanOrEqual($after,    $company->createdAt);
        $this->assertGreaterThanOrEqual($before, $company->updatedAt);
    }

    public function testAcceptsAllOptionalFields(): void
    {
        $company = new Company(
            id:           CompanyId::generate(),
            name:         new CompanyName('Test'),
            email:        'contact@test.com',
            phoneNumber:  '+1234567890',
            website:      'https://test.com',
            addressLine1: '1 Main Street',
            addressLine2: 'Suite 100',
            city:         'Paris',
            postalCode:   '75001',
            country:      'France',
            isActive:     false,
        );

        $this->assertSame('contact@test.com', $company->email);
        $this->assertSame('+1234567890',      $company->phoneNumber);
        $this->assertSame('https://test.com', $company->website);
        $this->assertSame('1 Main Street',    $company->addressLine1);
        $this->assertSame('Suite 100',        $company->addressLine2);
        $this->assertSame('Paris',            $company->city);
        $this->assertSame('75001',            $company->postalCode);
        $this->assertSame('France',           $company->country);
        $this->assertFalse($company->isActive);
    }

    public function testAcceptsExplicitTimestamps(): void
    {
        $created = new DateTimeImmutable('2024-01-01 10:00:00');
        $deleted = new DateTimeImmutable('2024-06-01 09:00:00');

        $company = new Company(
            id:        CompanyId::generate(),
            name:      new CompanyName('Test'),
            createdAt: $created,
            deletedAt: $deleted,
        );

        $this->assertSame($created, $company->createdAt);
        $this->assertSame($deleted, $company->deletedAt);
    }
}
