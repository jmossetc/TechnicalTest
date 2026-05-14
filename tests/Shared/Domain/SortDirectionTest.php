<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Shared\Domain;

use Mossetc\TechnicalTest\Shared\Domain\SortDirection;
use PHPUnit\Framework\TestCase;

final class SortDirectionTest extends TestCase
{
    public function testAscHasCorrectValue(): void
    {
        $this->assertSame('asc', SortDirection::Asc->value);
    }

    public function testDescHasCorrectValue(): void
    {
        $this->assertSame('desc', SortDirection::Desc->value);
    }

    public function testFromStringAsc(): void
    {
        $this->assertSame(SortDirection::Asc, SortDirection::from('asc'));
    }

    public function testFromStringDesc(): void
    {
        $this->assertSame(SortDirection::Desc, SortDirection::from('desc'));
    }
}
