<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Shop\Domain;

use DateTimeImmutable;
use InvalidArgumentException;
use Mossetc\TechnicalTest\Shop\Domain\Model\ShopSearchCriteria;
use PHPUnit\Framework\TestCase;

final class ShopSearchCriteriaTest extends TestCase
{
    public function testAllFieldsNullByDefault(): void
    {
        $c = new ShopSearchCriteria();

        $this->assertNull($c->companyId);
        $this->assertNull($c->name);
        $this->assertNull($c->email);
        $this->assertNull($c->phoneNumber);
        $this->assertNull($c->city);
        $this->assertNull($c->postalCode);
        $this->assertNull($c->country);
        $this->assertNull($c->isDigital);
        $this->assertNull($c->createdFrom);
        $this->assertNull($c->createdTo);
    }

    public function testAcceptsAllFields(): void
    {
        $from = new DateTimeImmutable('2025-01-01');
        $to   = new DateTimeImmutable('2025-12-31');

        $c = new ShopSearchCriteria(
            companyId:   '11111111-1111-4111-8111-111111111111',
            name:        'Flagship',
            email:       'shop@',
            phoneNumber: '+33',
            city:        'Paris',
            postalCode:  '75',
            country:     'France',
            isDigital:   true,
            createdFrom: $from,
            createdTo:   $to,
        );

        $this->assertSame('11111111-1111-4111-8111-111111111111', $c->companyId);
        $this->assertSame('Flagship', $c->name);
        $this->assertTrue($c->isDigital);
        $this->assertSame($from, $c->createdFrom);
        $this->assertSame($to, $c->createdTo);
    }

    public function testRejectsCreatedFromAfterCreatedTo(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('created_from must not be after created_to');

        new ShopSearchCriteria(
            createdFrom: new DateTimeImmutable('2025-02-01'),
            createdTo:   new DateTimeImmutable('2025-01-01'),
        );
    }

    public function testSameDateBoundariesAreValid(): void
    {
        $date = new DateTimeImmutable('2025-06-15');

        $c = new ShopSearchCriteria(createdFrom: $date, createdTo: $date);

        $this->assertSame($date, $c->createdFrom);
        $this->assertSame($date, $c->createdTo);
    }
}
