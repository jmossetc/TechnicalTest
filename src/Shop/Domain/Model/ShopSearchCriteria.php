<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Shop\Domain\Model;

use DateTimeImmutable;
use InvalidArgumentException;

final readonly class ShopSearchCriteria
{
    public function __construct(
        public ?string            $companyId = null,
        public ?string            $name = null,
        public ?string            $email = null,
        public ?string            $phoneNumber = null,
        public ?string            $city = null,
        public ?string            $postalCode = null,
        public ?string            $country = null,
        public ?bool              $isDigital = null,
        public ?DateTimeImmutable $createdFrom = null,
        public ?DateTimeImmutable $createdTo = null,
    ) {
        if ($this->createdFrom !== null && $this->createdTo !== null
            && $this->createdFrom > $this->createdTo) {
            throw new InvalidArgumentException('created_from must not be after created_to');
        }
    }
}
