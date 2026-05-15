<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Shared\Infrastructure\Clock;

use DateTimeImmutable;
use Mossetc\TechnicalTest\Shared\Infrastructure\Clock\SystemClock;
use PHPUnit\Framework\TestCase;

final class SystemClockTest extends TestCase
{
    public function testNowIsApproximatelyCurrentTime(): void
    {
        $before = new DateTimeImmutable();
        $now    = new SystemClock()->now();
        $after  = new DateTimeImmutable();

        self::assertGreaterThanOrEqual($before, $now);
        self::assertLessThanOrEqual($after, $now);
    }
}
