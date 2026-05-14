<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Shop\Domain\Model;

use Mossetc\TechnicalTest\Shared\Infrastructure\UUID\UUID;

final readonly class ShopId
{
    public function __construct(public string $value)
    {
        UUID::validate($value);
    }

    public static function generate(): self
    {
        return new self(UUID::generate());
    }

    public function equals(self $other): bool
    {
        return strtolower($this->value) === strtolower($other->value);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
