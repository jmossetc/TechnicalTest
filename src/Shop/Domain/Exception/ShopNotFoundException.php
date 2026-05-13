<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Shop\Domain\Exception;

use RuntimeException;

final class ShopNotFoundException extends RuntimeException
{
    public function __construct(string $id)
    {
        parent::__construct("Shop '{$id}' not found");
    }
}
