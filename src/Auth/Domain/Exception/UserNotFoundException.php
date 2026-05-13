<?php

namespace Mossetc\TechnicalTest\Auth\Domain\Exception;

final class UserNotFoundException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct("User not found");
    }
}