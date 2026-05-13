<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Integration\Support;

use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;

abstract class DatabaseTestCase extends TestCase
{
    protected PDO $pdo;

    protected function setUp(): void
    {
        try {
            $this->pdo = $this->connect();
        } catch (PDOException $e) {
            $this->markTestSkipped("MySQL not available: {$e->getMessage()}");
        }

        $this->applySchema();
        $this->truncate();
    }

    private function connect(): PDO
    {
        return new PDO(
            sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                (string) (getenv('DB_HOST') ?: '127.0.0.1'),
                (string) (getenv('DB_PORT') ?: '3306'),
                (string) (getenv('DB_DATABASE') ?: 'technical_test'),
            ),
            (string) (getenv('DB_USERNAME') ?: 'app'),
            (string) (getenv('DB_PASSWORD') ?: 'secret'),
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ],
        );
    }

    private function applySchema(): void
    {
        $path = __DIR__ . '/../../../database/schema.sql';
        $sql  = file_get_contents($path);

        if ($sql === false) {
            $this->fail("Cannot read schema file: {$path}");
        }

        // Drop existing tables so schema changes are always applied cleanly.
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        $this->pdo->exec('DROP TABLE IF EXISTS user_shop_roles, user_company_roles, user_admin_roles, shops, companies, users');
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

        $this->pdo->exec($sql);
    }

    private function truncate(): void
    {
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        $this->pdo->exec('TRUNCATE TABLE users');
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    }
}
