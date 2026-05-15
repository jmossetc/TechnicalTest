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

        self::assertNull($c->name);
        self::assertNull($c->email);
        self::assertNull($c->phoneNumber);
        self::assertNull($c->city);
        self::assertNull($c->postalCode);
        self::assertNull($c->country);
        self::assertNull($c->createdFrom);
        self::assertNull($c->createdTo);
    }

    public function testAcceptsAllFields(): void
    {
        $from = new DateTimeImmutable('2025-01-01');
        $to   = new DateTimeImmutable('2025-12-31');

        $c = new CompanySearchCriteria(
            name: 'Acme',
            email: 'contact@',
            phoneNumber: '06',
            city: 'Paris',
            postalCode: '75',
            country: 'France',
            createdFrom: $from,
            createdTo: $to,
        );

        self::assertSame('Acme', $c->name);
        self::assertSame('Paris', $c->city);
        self::assertSame($from, $c->createdFrom);
        self::assertSame($to, $c->createdTo);
    }

    public function testRejectsCreatedFromAfterCreatedTo(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('created_from must not be after created_to');

        new CompanySearchCriteria(
            createdFrom: new DateTimeImmutable('2025-02-01'),
            createdTo: new DateTimeImmutable('2025-01-01'),
        );
    }

    public function testSameDateBoundariesAreValid(): void
    {
        $date = new DateTimeImmutable('2025-06-15');

        $c = new CompanySearchCriteria(createdFrom: $date, createdTo: $date);

        self::assertSame($date, $c->createdFrom);
        self::assertSame($date, $c->createdTo);
    }
}
