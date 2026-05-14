<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Company\Domain;

use DateTimeImmutable;
use InvalidArgumentException;
use Mossetc\TechnicalTest\Company\Domain\Model\CompanySearchCriteria;
use PHPUnit\Framework\TestCase;

final class CompanySearchCriteriaTest extends TestCase
{
    public function testAllFieldsNullByDefault(): void
    {
        $c = new CompanySearchCriteria();

        $this->assertNull($c->name);
        $this->assertNull($c->email);
        $this->assertNull($c->phoneNumber);
        $this->assertNull($c->city);
        $this->assertNull($c->postalCode);
        $this->assertNull($c->country);
        $this->assertNull($c->createdFrom);
        $this->assertNull($c->createdTo);
    }

    public function testAcceptsAllFields(): void
    {
        $from = new DateTimeImmutable('2025-01-01');
        $to   = new DateTimeImmutable('2025-12-31');

        $c = new CompanySearchCriteria(
            name:        'Acme',
            email:       'contact@',
            phoneNumber: '06',
            city:        'Paris',
            postalCode:  '75',
            country:     'France',
            createdFrom: $from,
            createdTo:   $to,
        );

        $this->assertSame('Acme', $c->name);
        $this->assertSame('Paris', $c->city);
        $this->assertSame($from, $c->createdFrom);
        $this->assertSame($to, $c->createdTo);
    }

    public function testRejectsCreatedFromAfterCreatedTo(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('created_from must not be after created_to');

        new CompanySearchCriteria(
            createdFrom: new DateTimeImmutable('2025-02-01'),
            createdTo:   new DateTimeImmutable('2025-01-01'),
        );
    }

    public function testSameDateBoundariesAreValid(): void
    {
        $date = new DateTimeImmutable('2025-06-15');

        $c = new CompanySearchCriteria(createdFrom: $date, createdTo: $date);

        $this->assertSame($date, $c->createdFrom);
        $this->assertSame($date, $c->createdTo);
    }
}
