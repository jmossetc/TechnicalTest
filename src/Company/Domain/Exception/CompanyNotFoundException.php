<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Company\Domain\Exception;

use RuntimeException;

final class CompanyNotFoundException extends RuntimeException
{
    public function __construct(string $id)
    {
        parent::__construct("Company '{$id}' not found");
    }
}
