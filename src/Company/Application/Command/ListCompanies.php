<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Company\Application\Command;

use InvalidArgumentException;

final readonly class ListCompanies
{
    public function __construct(
        public int $page = 1,
        public int $limit = 10,
        public ?string $name = null,
    ) {
        if ($this->page < 1) {
            throw new InvalidArgumentException('Page must be at least 1');
        }

        if ($this->limit < 1 || $this->limit > 100) {
            throw new InvalidArgumentException('Limit must be between 1 and 100');
        }
    }
}
