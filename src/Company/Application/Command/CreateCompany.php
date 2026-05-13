<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Company\Application\Command;

final readonly class CreateCompany
{
    public function __construct(public string $name) {}
}
