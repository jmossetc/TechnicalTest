<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Infrastructure\DI;

use PDO;

final class PdoFactory
{
    public static function create(
        string $host,
        string $port,
        string $database,
        string $username,
        string $password,
    ): PDO {
        return new PDO(
            sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $database),
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ],
        );
    }
}
