<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Application\Command;

use Mossetc\TechnicalTest\Auth\Application\DTO\UserUpdateInput;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserId;

final readonly class UpdateUser
{
    public function __construct(
        public UserId          $callerId,
        public string          $targetId,
        public UserUpdateInput $input,
    ) {}
}
