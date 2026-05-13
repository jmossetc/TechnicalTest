<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Domain\Exception;

use Mossetc\TechnicalTest\Auth\Domain\Email;
use RuntimeException;

final class UserAlreadyExistsException extends RuntimeException
{
    public function __construct(Email $email)
    {
        parent::__construct("User with email '{$email->value}' already exists");
    }
}
