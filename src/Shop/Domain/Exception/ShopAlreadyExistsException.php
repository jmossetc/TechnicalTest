<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Shop\Domain\Exception;

use Mossetc\TechnicalTest\Shop\Domain\Model\ShopName;
use RuntimeException;

final class ShopAlreadyExistsException extends RuntimeException
{
    public function __construct(ShopName $name)
    {
        parent::__construct("A shop named '{$name->value}' already exists in this company");
    }
}
