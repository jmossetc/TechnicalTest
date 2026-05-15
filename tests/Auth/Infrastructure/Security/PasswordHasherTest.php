<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Auth\Infrastructure\Security;

use Mossetc\TechnicalTest\Auth\Domain\Model\PlainPassword;
use Mossetc\TechnicalTest\Auth\Infrastructure\Security\PasswordHasher;
use PHPUnit\Framework\TestCase;

final class PasswordHasherTest extends TestCase
{
    public function testHashIsNotPlaintext(): void
    {
        $hasher = new PasswordHasher('');
        $plain  = new PlainPassword('secret123');

        $hash = $hasher->hash($plain);

        self::assertNotSame($plain->value, $hash->hash);
    }

    public function testVerifiesCorrectPassword(): void
    {
        $hasher = new PasswordHasher('');
        $plain  = new PlainPassword('secret123');

        self::assertTrue($hasher->verify($plain, $hasher->hash($plain)));
    }

    public function testRejectsWrongPassword(): void
    {
        $hasher = new PasswordHasher('');
        $hash   = $hasher->hash(new PlainPassword('correct123'));

        self::assertFalse($hasher->verify(new PlainPassword('wrongpass1'), $hash));
    }

    public function testTwoHashesOfSamePasswordDiffer(): void
    {
        $hasher = new PasswordHasher('');
        $plain  = new PlainPassword('secret123');

        self::assertNotSame($hasher->hash($plain)->hash, $hasher->hash($plain)->hash);
    }

    public function testEmptyPepperBehavesLikeNoPepper(): void
    {
        $hasher = new PasswordHasher('');
        $plain  = new PlainPassword('mypassword');
        $hash   = $hasher->hash($plain);

        self::assertTrue($hasher->verify($plain, $hash));
    }

    public function testPepperChangesHash(): void
    {
        $plain    = new PlainPassword('mypassword');
        $noPepper = new PasswordHasher('');
        $pepper   = new PasswordHasher('supersecretpeppervalue32chars!!');

        $hashA = $noPepper->hash($plain);
        $hashB = $pepper->hash($plain);

        self::assertNotSame($hashA->hash, $hashB->hash);
    }

    public function testHashedWithPepperCannotBeVerifiedWithoutPepper(): void
    {
        $plain    = new PlainPassword('mypassword');
        $noPepper = new PasswordHasher('');
        $pepper   = new PasswordHasher('supersecretpeppervalue32chars!!');

        $hash = $pepper->hash($plain);

        self::assertFalse($noPepper->verify($plain, $hash));
    }

    public function testPepperRoundTrip(): void
    {
        $plain  = new PlainPassword('mypassword');
        $hasher = new PasswordHasher('supersecretpeppervalue32chars!!');

        self::assertTrue($hasher->verify($plain, $hasher->hash($plain)));
    }
}
