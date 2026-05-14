<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Company\Domain\Model;

use DateTimeImmutable;
use InvalidArgumentException;

final readonly class CompanySearchCriteria
{
    public function __construct(
        public ?string            $name = null,
        public ?string            $email = null,
        public ?string            $phoneNumber = null,
        public ?string            $city = null,
        public ?string            $postalCode = null,
        public ?string            $country = null,
        public ?DateTimeImmutable $createdFrom = null,
        public ?DateTimeImmutable $createdTo = null,
    ) {
        if ($this->createdFrom !== null && $this->createdTo !== null
            && $this->createdFrom > $this->createdTo) {
            throw new InvalidArgumentException('created_from must not be after created_to');
        }
    }
}
