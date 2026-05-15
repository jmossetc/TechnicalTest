<?php

declare(strict_types=1);

use Mossetc\TechnicalTest\Shared\Infrastructure\DI\ContainerFactory;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Request;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Router;

require_once __DIR__ . '/../vendor/autoload.php';

// @Todo - Run this code only in dev environment
//set_exception_handler(static function (Throwable $e): never {
//    http_response_code(500);
//    header('Content-Type: application/json; charset=utf-8');
//    echo json_encode(['error' => 'Internal Server Error'], JSON_THROW_ON_ERROR);
//    exit(1);
//});

$container = ContainerFactory::build();

$router = $container->get(Router::class);
if (!$router instanceof Router) {
    throw new RuntimeException('Router service not found in container');
}

$router->dispatch(Request::fromGlobals())->send();
