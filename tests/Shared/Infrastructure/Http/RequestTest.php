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
            method:     'POST',
            path:       '/api/users',
            headers:    ['Content-Type' => 'application/json'],
            body:       ['email' => 'a@b.com'],
            attributes: ['id' => '123'],
            query:      ['page' => '2'],
        );

        $this->assertSame('POST',                               $request->method);
        $this->assertSame('/api/users',                        $request->path);
        $this->assertSame(['Content-Type' => 'application/json'], $request->headers);
        $this->assertSame(['email' => 'a@b.com'],               $request->body);
        $this->assertSame(['id' => '123'],                      $request->attributes);
        $this->assertSame(['page' => '2'],                      $request->query);
    }

    public function testAttributesAndQueryDefaultToEmpty(): void
    {
        $request = new Request('GET', '/api/users', [], []);

        $this->assertSame([], $request->attributes);
        $this->assertSame([], $request->query);
    }

    // ── withAttributes() ─────────────────────────────────────────────────────

    public function testWithAttributesReturnsNewInstance(): void
    {
        $original = new Request('GET', '/api/users/123', [], []);
        $updated  = $original->withAttributes(['id' => '123']);

        $this->assertNotSame($original, $updated);
    }

    public function testWithAttributesSetsAttributes(): void
    {
        $request  = new Request('GET', '/api/users/123', [], []);
        $updated  = $request->withAttributes(['id' => '123', 'extra' => 'val']);

        $this->assertSame(['id' => '123', 'extra' => 'val'], $updated->attributes);
    }

    public function testWithAttributesPreservesOtherProperties(): void
    {
        $request = new Request('DELETE', '/api/users/5', ['Authorization' => 'Bearer tok'], ['x' => 1], [], ['q' => 'a']);
        $updated  = $request->withAttributes(['id' => '5']);

        $this->assertSame('DELETE',                         $updated->method);
        $this->assertSame('/api/users/5',                   $updated->path);
        $this->assertSame(['Authorization' => 'Bearer tok'], $updated->headers);
        $this->assertSame(['x' => 1],                       $updated->body);
        $this->assertSame(['q' => 'a'],                     $updated->query);
    }

    public function testWithAttributesReplacesExistingAttributes(): void
    {
        $request = new Request('GET', '/', [], [], ['old' => 'val']);
        $updated  = $request->withAttributes(['new' => 'val']);

        $this->assertSame(['new' => 'val'], $updated->attributes);
        $this->assertArrayNotHasKey('old', $updated->attributes);
    }

    // ── stringBody() ─────────────────────────────────────────────────────────

    public function testStringBodyReturnsStringValue(): void
    {
        $request = new Request('POST', '/', [], ['name' => 'Alice']);

        $this->assertSame('Alice', $request->stringBody('name'));
    }

    public function testStringBodyReturnsDefaultWhenKeyAbsent(): void
    {
        $request = new Request('POST', '/', [], []);

        $this->assertSame('', $request->stringBody('missing'));
    }

    public function testStringBodyReturnsCustomDefaultWhenKeyAbsent(): void
    {
        $request = new Request('POST', '/', [], []);

        $this->assertSame('fallback', $request->stringBody('missing', 'fallback'));
    }

    public function testStringBodyReturnsDefaultForNonStringValue(): void
    {
        $request = new Request('POST', '/', [], ['count' => 42]);

        $this->assertSame('', $request->stringBody('count'));
    }

    public function testStringBodyReturnsDefaultForNullValue(): void
    {
        $request = new Request('POST', '/', [], ['field' => null]);

        $this->assertSame('', $request->stringBody('field'));
    }

    public function testStringBodyReturnsEmptyStringWhenValueIsEmptyString(): void
    {
        $request = new Request('POST', '/', [], ['email' => '']);

        $this->assertSame('', $request->stringBody('email'));
    }

    public function testStringBodyReturnsBoolDefaultForBooleanValue(): void
    {
        $request = new Request('POST', '/', [], ['active' => true]);

        $this->assertSame('', $request->stringBody('active'));
    }
}
