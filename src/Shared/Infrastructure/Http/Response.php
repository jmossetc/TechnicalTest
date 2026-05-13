<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Shared\Infrastructure\Http;

final readonly class Response
{
    private function __construct(
        private mixed $data,
        private int $status,
    ) {}

    /** @param array<string, mixed>|array<mixed> $data */
    public static function json(array $data, int $status = 200): self
    {
        return new self($data, $status);
    }

    public static function error(string $message, int $status = 400): self
    {
        return new self(['error' => $message], $status);
    }

    public function send(): void
    {
        http_response_code($this->status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($this->data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    public function status(): int
    {
        return $this->status;
    }

    /** @return mixed */
    public function data(): mixed
    {
        return $this->data;
    }
}
