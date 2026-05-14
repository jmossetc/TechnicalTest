<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Shared\Infrastructure\Date;

use DateTimeImmutable;
use Psr\Clock\ClockInterface;

final readonly class Date
{
    public static function parseDate(string $raw, bool $endOfDay = false): ?DateTimeImmutable
    {
        if ($raw === '') {
            return null;
        }

        $dt     = DateTimeImmutable::createFromFormat('Y-m-d', $raw);
        $errors = DateTimeImmutable::getLastErrors();

        if ($dt === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            throw new \InvalidArgumentException("Invalid date format '{$raw}', expected Y-m-d");
        }

        return $endOfDay ? $dt->setTime(23, 59, 59) : $dt->setTime(0, 0, 0);
    }
}
