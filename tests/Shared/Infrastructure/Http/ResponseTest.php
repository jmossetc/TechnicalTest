<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Shared\Infrastructure\Http;

use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Response;
use PHPUnit\Framework\TestCase;

final class ResponseTest extends TestCase
{
    // ── json() ────────────────────────────────────────────────────────────────

    public function testJsonDefaultsTo200(): void
    {
        $response = Response::json(['key' => 'value']);

        $this->assertSame(200, $response->status());
    }

    public function testJsonStoresData(): void
    {
        $data     = ['id' => '123', 'name' => 'Alice'];
        $response = Response::json($data);

        $this->assertSame($data, $response->data());
    }

    public function testJsonAcceptsCustomStatus(): void
    {
        $response = Response::json(['id' => '1'], 201);

        $this->assertSame(201, $response->status());
    }

    public function testJsonAcceptsEmptyArray(): void
    {
        $response = Response::json([]);

        $this->assertSame([], $response->data());
        $this->assertSame(200, $response->status());
    }

    // ── error() ───────────────────────────────────────────────────────────────

    public function testErrorDefaultsTo400(): void
    {
        $response = Response::error('Something went wrong');

        $this->assertSame(400, $response->status());
    }

    public function testErrorWrapsMessageInErrorKey(): void
    {
        $response = Response::error('Not found');

        $this->assertSame(['error' => 'Not found'], $response->data());
    }

    public function testErrorAcceptsCustomStatus(): void
    {
        $response = Response::error('Unauthorized', 401);

        $this->assertSame(401, $response->status());
    }

    public function testError404(): void
    {
        $response = Response::error('Not Found', 404);

        $this->assertSame(404, $response->status());
        $this->assertSame('Not Found', $response->data()['error']);
    }

    public function testError422(): void
    {
        $response = Response::error('Validation failed', 422);

        $this->assertSame(422, $response->status());
    }

    public function testError500(): void
    {
        $response = Response::error('Internal error', 500);

        $this->assertSame(500, $response->status());
    }
}
