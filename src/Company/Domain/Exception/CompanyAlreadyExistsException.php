<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Company\Domain\Exception;

use Mossetc\TechnicalTest\Company\Domain\Model\CompanyName;
use RuntimeException;

final class CompanyAlreadyExistsException extends RuntimeException
{
    public function __construct(CompanyName $name)
    {
        parent::__construct("Company '{$name->value}' already exists");
    }
}
