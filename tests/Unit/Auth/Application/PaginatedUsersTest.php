<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Unit\Auth\Application;

use Mossetc\TechnicalTest\Auth\Application\Query\PaginatedUsers;
use PHPUnit\Framework\TestCase;

final class PaginatedUsersTest extends TestCase
{
    public function testCalculatesPagesCeiling(): void
    {
        $result = new PaginatedUsers(users: [], total: 25, page: 1, limit: 10);

        $this->assertSame(3, $result->pages());
    }

    public function testExactDivisionProducesNoExtraPage(): void
    {
        $result = new PaginatedUsers(users: [], total: 20, page: 1, limit: 10);

        $this->assertSame(2, $result->pages());
    }

    public function testZeroTotalReturnsZeroPages(): void
    {
        $result = new PaginatedUsers(users: [], total: 0, page: 1, limit: 10);

        $this->assertSame(0, $result->pages());
    }

    public function testFewerItemsThanLimitIsOnePage(): void
    {
        $result = new PaginatedUsers(users: [], total: 5, page: 1, limit: 10);

        $this->assertSame(1, $result->pages());
    }

    public function testSingleItemSinglePage(): void
    {
        $result = new PaginatedUsers(users: [], total: 1, page: 1, limit: 1);

        $this->assertSame(1, $result->pages());
    }
}
