<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Company\Application;

use Mossetc\TechnicalTest\Company\Application\DTO\PaginatedCompanies;
use PHPUnit\Framework\TestCase;

final class PaginatedCompaniesTest extends TestCase
{
    public function testPagesCalculatesCeiling(): void
    {
        $dto = new PaginatedCompanies(companies: [], total: 25, page: 1, limit: 10);

        self::assertSame(3, $dto->pages());
    }

    public function testExactDivisionProducesNoExtraPage(): void
    {
        $dto = new PaginatedCompanies(companies: [], total: 20, page: 1, limit: 10);

        self::assertSame(2, $dto->pages());
    }

    public function testZeroTotalReturnsZeroPages(): void
    {
        $dto = new PaginatedCompanies(companies: [], total: 0, page: 1, limit: 10);

        self::assertSame(0, $dto->pages());
    }

    public function testFewerItemsThanLimitIsOnePage(): void
    {
        $dto = new PaginatedCompanies(companies: [], total: 3, page: 1, limit: 10);

        self::assertSame(1, $dto->pages());
    }

    public function testHoldsAllFields(): void
    {
        $dto = new PaginatedCompanies(companies: [], total: 5, page: 2, limit: 10);

        self::assertSame([], $dto->companies);
        self::assertSame(5, $dto->total);
        self::assertSame(2, $dto->page);
        self::assertSame(10, $dto->limit);
    }
}
