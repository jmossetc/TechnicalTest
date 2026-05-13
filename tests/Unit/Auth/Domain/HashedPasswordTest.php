<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Unit\Auth\Domain;

use InvalidArgumentException;
use Mossetc\TechnicalTest\Auth\Domain\HashedPassword;
use Mossetc\TechnicalTest\Auth\Domain\PlainPassword;
use PHPUnit\Framework\TestCase;

final class HashedPasswordTest extends TestCase
{
    public function testHashesPlainPassword(): void
    {
        $plain = new PlainPassword('secret123');
        $hashed = HashedPassword::fromPlain($plain);

        $this->assertNotSame($plain->value, $hashed->hash);
    }

    public function testVerifiesCorrectPassword(): void
    {
        $plain = new PlainPassword('secret123');
        $hashed = HashedPassword::fromPlain($plain);

        $this->assertTrue($hashed->verify($plain));
    }

    public function testRejectsWrongPassword(): void
    {
        $hashed = HashedPassword::fromPlain(new PlainPassword('secret123'));

        $this->assertFalse($hashed->verify(new PlainPassword('wrongpass')));
    }

    public function testFromHashPreservesExistingHash(): void
    {
        $plain = new PlainPassword('secret123');
        $original = HashedPassword::fromPlain($plain);

        $restored = HashedPassword::fromHash($original->hash);

        $this->assertTrue($restored->verify($plain));
    }

    public function testFromHashRejectsInvalidHash(): void
    {
        $this->expectException(InvalidArgumentException::class);

        HashedPassword::fromHash('short');
    }

    public function testTwoHashesOfSamePasswordDiffer(): void
    {
        $plain = new PlainPassword('secret123');

        $hash1 = HashedPassword::fromPlain($plain);
        $hash2 = HashedPassword::fromPlain($plain);

        $this->assertNotSame($hash1->hash, $hash2->hash);
    }
}
