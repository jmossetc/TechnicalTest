<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Auth\Domain\Model;

use InvalidArgumentException;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserId;
use PHPUnit\Framework\TestCase;

final class UserIdTest extends TestCase
{
    public function testGeneratesValidUuidV4(): void
    {
        $id = UserId::generate();

        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $id->value,
        );
    }

    public function testGeneratesUniqueIds(): void
    {
        $a = UserId::generate();
        $b = UserId::generate();

        self::assertFalse($a->equals($b));
    }

    public function testAcceptsValidUuid(): void
    {
        $id = new UserId('550e8400-e29b-41d4-a716-446655440000');

        self::assertSame('550e8400-e29b-41d4-a716-446655440000', $id->value);
    }

    public function testRejectsInvalidUuid(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new UserId('not-a-uuid');
    }

    public function testRejectsUuidV1(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new UserId('550e8400-e29b-11d4-a716-446655440000');
    }

    public function testEquality(): void
    {
        $id = UserId::generate();
        $same = new UserId($id->value);

        self::assertTrue($id->equals($same));
    }

    public function testEqualityIsCaseInsensitive(): void
    {
        $id = new UserId('550e8400-e29b-41d4-a716-446655440000');
        $upper = new UserId('550E8400-E29B-41D4-A716-446655440000');

        self::assertTrue($id->equals($upper));
    }

    public function testToString(): void
    {
        $id = UserId::generate();

        self::assertSame($id->value, (string) $id);
    }
}
