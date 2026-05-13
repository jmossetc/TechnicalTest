<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Domain\Model;

use InvalidArgumentException;

final readonly class LastName
{
    public function __construct(public string $value)
    {
        if (trim($value) === '') {
            throw new InvalidArgumentException('Last name cannot be empty');
        }

        if (strlen($value) > 255) {
            throw new InvalidArgumentException('Last name cannot exceed 255 characters');
        }
    }
}
