<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Domain\Service;

use Mossetc\TechnicalTest\Auth\Domain\Model\AuthToken;
use Mossetc\TechnicalTest\Auth\Domain\Model\Email;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserId;
use Mossetc\TechnicalTest\Auth\Domain\Exception\InvalidTokenException;

interface TokenServiceInterface
{
    public function issue(UserId $userId, Email $email): AuthToken;

    /**
     * @throws InvalidTokenException
     */
    public function validate(string $token): UserId;
}
