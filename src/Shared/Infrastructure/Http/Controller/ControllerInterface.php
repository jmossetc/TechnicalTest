<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Shared\Infrastructure\Http\Controller;

use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Request;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Response;

interface ControllerInterface
{
    public function __invoke(Request $request): Response;
}
