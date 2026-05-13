<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Company\Application\Command;

final readonly class UpdateCompany
{
    public function __construct(
        public string $id,
        public string $name,
    ) {}
}
