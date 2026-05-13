<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Domain\Exception;

use RuntimeException;
use Throwable;

final class InvalidTokenException extends RuntimeException
{
    public function __construct(string $reason, ?Throwable $previous = null)
    {
        parent::__construct("Invalid token: {$reason}", previous: $previous);
    }
}
