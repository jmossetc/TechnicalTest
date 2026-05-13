<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Domain\Service;

use Mossetc\TechnicalTest\Auth\Domain\Model\HashedPassword;
use Mossetc\TechnicalTest\Auth\Domain\Model\PlainPassword;

interface PasswordHasherInterface
{
    public function hash(PlainPassword $password): HashedPassword;

    public function verify(PlainPassword $password, HashedPassword $hash): bool;
}
