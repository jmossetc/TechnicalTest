<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Company\Domain\Model;

use DateTimeImmutable;

final readonly class Company
{
    public function __construct(
        public CompanyId $id,
        public CompanyName $name,
        public DateTimeImmutable $createdAt = new DateTimeImmutable(),
        public DateTimeImmutable $updatedAt = new DateTimeImmutable(),
        public ?DateTimeImmutable $deletedAt = null,
    ) {}
}
