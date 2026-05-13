<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Shared\Infrastructure\Clock;

use DateTimeImmutable;
use Mossetc\TechnicalTest\Shared\Infrastructure\Clock\SystemClock;
use PHPUnit\Framework\TestCase;

final class SystemClockTest extends TestCase
{
    public function testNowReturnsDateTimeImmutable(): void
    {
        $this->assertInstanceOf(DateTimeImmutable::class, (new SystemClock())->now());
    }

    public function testNowIsApproximatelyCurrentTime(): void
    {
        $before = new DateTimeImmutable();
        $now    = (new SystemClock())->now();
        $after  = new DateTimeImmutable();

        $this->assertGreaterThanOrEqual($before, $now);
        $this->assertLessThanOrEqual($after, $now);
    }

    public function testSuccessiveCallsAdvanceTime(): void
    {
        $clock = new SystemClock();
        $first = $clock->now();
        usleep(1000);
        $second = $clock->now();

        $this->assertGreaterThanOrEqual($first, $second);
    }
}
