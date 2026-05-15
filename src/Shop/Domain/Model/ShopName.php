<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Shop\Domain\Model;

use InvalidArgumentException;

final readonly class ShopName
{
    public function __construct(public string $value)
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            throw new InvalidArgumentException('Shop name cannot be empty');
        }

        if (\strlen($trimmed) > 255) {
            throw new InvalidArgumentException('Shop name cannot exceed 255 characters');
        }
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
