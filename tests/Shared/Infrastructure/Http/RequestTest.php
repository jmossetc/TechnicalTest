<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Shared\Infrastructure\Http;

use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Request;
use PHPUnit\Framework\TestCase;

final class RequestTest extends TestCase
{
    // ── Constructor ───────────────────────────────────────────────────────────

    public function testHoldsConstructorValues(): void
    {
        $request = new Request(
            method: 'POST',
            path: '/api/users',
            headers: ['Content-Type' => 'application/json'],
            body: ['email' => 'a@b.com'],
            attributes: ['id' => '123'],
            query: ['page' => '2'],
        );

        self::assertSame('POST', $request->method);
        self::assertSame('/api/users', $request->path);
        self::assertSame(['Content-Type' => 'application/json'], $request->headers);
        self::assertSame(['email' => 'a@b.com'], $request->body);
        self::assertSame(['id' => '123'], $request->attributes);
        self::assertSame(['page' => '2'], $request->query);
    }

    public function testAttributesAndQueryDefaultToEmpty(): void
    {
        $request = new Request('GET', '/api/users', [], []);

        self::assertSame([], $request->attributes);
        self::assertSame([], $request->query);
    }

    // ── withAttributes() ─────────────────────────────────────────────────────

    public function testWithAttributesReturnsNewInstance(): void
    {
        $original = new Request('GET', '/api/users/123', [], []);
        $updated  = $original->withAttributes(['id' => '123']);

        self::assertNotSame($original, $updated);
    }

    public function testWithAttributesSetsAttributes(): void
    {
        $request  = new Request('GET', '/api/users/123', [], []);
        $updated  = $request->withAttributes(['id' => '123', 'extra' => 'val']);

        self::assertSame(['id' => '123', 'extra' => 'val'], $updated->attributes);
    }

    public function testWithAttributesPreservesOtherProperties(): void
    {
        $request = new Request('DELETE', '/api/users/5', ['Authorization' => 'Bearer tok'], ['x' => 1], [], ['q' => 'a']);
        $updated  = $request->withAttributes(['id' => '5']);

        self::assertSame('DELETE', $updated->method);
        self::assertSame('/api/users/5', $updated->path);
        self::assertSame(['Authorization' => 'Bearer tok'], $updated->headers);
        self::assertSame(['x' => 1], $updated->body);
        self::assertSame(['q' => 'a'], $updated->query);
    }

    public function testWithAttributesReplacesExistingAttributes(): void
    {
        $request = new Request('GET', '/', [], [], ['old' => 'val']);
        $updated  = $request->withAttributes(['new' => 'val']);

        self::assertSame(['new' => 'val'], $updated->attributes);
        self::assertArrayNotHasKey('old', $updated->attributes);
    }

    // ── stringBody() ─────────────────────────────────────────────────────────

    public function testStringBodyReturnsStringValue(): void
    {
        $request = new Request('POST', '/', [], ['name' => 'Alice']);

        self::assertSame('Alice', $request->stringBody('name'));
    }

    public function testStringBodyReturnsDefaultWhenKeyAbsent(): void
    {
        $request = new Request('POST', '/', [], []);

        self::assertSame('', $request->stringBody('missing'));
    }

    public function testStringBodyReturnsCustomDefaultWhenKeyAbsent(): void
    {
        $request = new Request('POST', '/', [], []);

        self::assertSame('fallback', $request->stringBody('missing', 'fallback'));
    }

    public function testStringBodyReturnsDefaultForNonStringValue(): void
    {
        $request = new Request('POST', '/', [], ['count' => 42]);

        self::assertSame('', $request->stringBody('count'));
    }

    public function testStringBodyReturnsDefaultForNullValue(): void
    {
        $request = new Request('POST', '/', [], ['field' => null]);

        self::assertSame('', $request->stringBody('field'));
    }

    public function testStringBodyReturnsEmptyStringWhenValueIsEmptyString(): void
    {
        $request = new Request('POST', '/', [], ['email' => '']);

        self::assertSame('', $request->stringBody('email'));
    }

    public function testStringBodyReturnsBoolDefaultForBooleanValue(): void
    {
        $request = new Request('POST', '/', [], ['active' => true]);

        self::assertSame('', $request->stringBody('active'));
    }
}
