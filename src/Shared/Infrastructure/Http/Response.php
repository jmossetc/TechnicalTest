<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Shared\Infrastructure\Http;

final readonly class Response
{
    /** @param array<string, mixed> $data */
    private function __construct(
        private array $data,
        private int $status,
    ) {}

    /** @param \JsonSerializable|array<string, mixed> $data */
    public static function json(\JsonSerializable|array $data, int $status = 200): self
    {
        $payload = $data instanceof \JsonSerializable ? $data->jsonSerialize() : $data;

        return new self(self::normalize($payload), $status);
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

    /** @return array<string, mixed> */
    public function data(): array
    {
        return $this->data;
    }

    /** @return array<string, mixed> */
    private static function normalize(mixed $data): array
    {
        if (!\is_array($data)) {
            return [];
        }
        $result = [];
        foreach ($data as $key => $value) {
            if (\is_string($key)) {
                $result[$key] = self::normalizeValue($value);
            }
        }
        return $result;
    }

    private static function normalizeValue(mixed $value): mixed
    {
        if ($value instanceof \JsonSerializable) {
            return self::normalize($value->jsonSerialize());
        }
        if (\is_array($value)) {
            return array_map(self::normalizeValue(...), $value);
        }
        return $value;
    }
}
