<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Domain\Model;

use DateTimeImmutable;
use InvalidArgumentException;

final readonly class UserSearchCriteria
{
    public function __construct(
        public ?string            $email = null,
        public ?string            $firstName = null,
        public ?string            $lastName = null,
        public ?string            $phoneNumber = null,
        public ?Role              $role = null,
        public ?bool              $isActive = null,
        public ?DateTimeImmutable $createdFrom = null,
        public ?DateTimeImmutable $createdTo = null,
        public ?DateTimeImmutable $lastLoginFrom = null,
        public ?DateTimeImmutable $lastLoginTo = null,
    ) {
        if ($this->createdFrom !== null && $this->createdTo !== null
            && $this->createdFrom > $this->createdTo) {
            throw new InvalidArgumentException('created_from must not be after created_to');
        }

        if ($this->lastLoginFrom !== null && $this->lastLoginTo !== null
            && $this->lastLoginFrom > $this->lastLoginTo) {
            throw new InvalidArgumentException('last_login_from must not be after last_login_to');
        }
    }
}
