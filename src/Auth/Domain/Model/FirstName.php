<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Domain\Model;

use InvalidArgumentException;

final readonly class FirstName
{
    public function __construct(public string $value)
    {
        if (trim($value) === '') {
            throw new InvalidArgumentException('First name cannot be empty');
        }

        if (\strlen($value) > 255) {
            throw new InvalidArgumentException('First name cannot exceed 255 characters');
        }
    }
}
