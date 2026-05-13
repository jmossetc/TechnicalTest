<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Company\Application;

use InvalidArgumentException;
use Mossetc\TechnicalTest\Company\Application\Command\ListCompanies;
use PHPUnit\Framework\TestCase;

final class ListCompaniesCommandTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $cmd = new ListCompanies();

        $this->assertSame(1, $cmd->page);
        $this->assertSame(10, $cmd->limit);
        $this->assertNull($cmd->name);
    }

    public function testAcceptsCustomValues(): void
    {
        $cmd = new ListCompanies(page: 3, limit: 25, name: 'Acme');

        $this->assertSame(3,      $cmd->page);
        $this->assertSame(25,     $cmd->limit);
        $this->assertSame('Acme', $cmd->name);
    }

    public function testRejectsPageZero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ListCompanies(page: 0);
    }

    public function testRejectsNegativePage(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ListCompanies(page: -1);
    }

    public function testRejectsLimitZero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ListCompanies(limit: 0);
    }

    public function testRejectsLimitAbove100(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ListCompanies(limit: 101);
    }

    public function testAcceptsLimitBoundaries(): void
    {
        $this->assertSame(1,   (new ListCompanies(limit: 1))->limit);
        $this->assertSame(100, (new ListCompanies(limit: 100))->limit);
    }
}
