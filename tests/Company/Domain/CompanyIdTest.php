<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Company\Domain;

use InvalidArgumentException;
use Mossetc\TechnicalTest\Company\Domain\Model\CompanyId;
use PHPUnit\Framework\TestCase;

final class CompanyIdTest extends TestCase
{
    public function testGeneratesValidUuidV4(): void
    {
        $id = CompanyId::generate();

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $id->value,
        );
    }

    public function testGeneratesUniqueIds(): void
    {
        $this->assertFalse(CompanyId::generate()->equals(CompanyId::generate()));
    }

    public function testAcceptsValidUuid(): void
    {
        $id = new CompanyId('11111111-1111-4111-8111-111111111111');

        $this->assertSame('11111111-1111-4111-8111-111111111111', $id->value);
    }

    public function testRejectsPlainString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new CompanyId('not-a-uuid');
    }

    public function testRejectsEmptyString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new CompanyId('');
    }

    public function testRejectsUuidV1(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new CompanyId('550e8400-e29b-11d4-a716-446655440000');
    }

    public function testEquality(): void
    {
        $id   = CompanyId::generate();
        $same = new CompanyId($id->value);

        $this->assertTrue($id->equals($same));
    }

    public function testEqualityIsCaseInsensitive(): void
    {
        $lower = new CompanyId('11111111-1111-4111-8111-111111111111');
        $upper = new CompanyId('11111111-1111-4111-8111-111111111111');

        $this->assertTrue($lower->equals($upper));
    }

    public function testInequalityForDifferentIds(): void
    {
        $this->assertFalse(CompanyId::generate()->equals(CompanyId::generate()));
    }

    public function testToString(): void
    {
        $id = CompanyId::generate();

        $this->assertSame($id->value, (string) $id);
    }
}
