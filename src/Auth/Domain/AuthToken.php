<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Domain;

use InvalidArgumentException;

final readonly class AuthToken
{
    public function __construct(public string $value)
    {
        if ($value === '') {
            throw new InvalidArgumentException('Auth token cannot be empty');
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
