<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Auth\Presentation\Controller;

use libphonenumber\PhoneNumberUtil;
use Mossetc\TechnicalTest\Auth\Application\Handler\UpdateUserHandler;
use Mossetc\TechnicalTest\Auth\Domain\Exception\InvalidTokenException;
use Mossetc\TechnicalTest\Auth\Domain\Model\Email;
use Mossetc\TechnicalTest\Auth\Domain\Model\FirstName;
use Mossetc\TechnicalTest\Auth\Domain\Model\HashedPassword;
use Mossetc\TechnicalTest\Auth\Domain\Model\LastName;
use Mossetc\TechnicalTest\Auth\Domain\Model\PlainPassword;
use Mossetc\TechnicalTest\Auth\Domain\Model\Role;
use Mossetc\TechnicalTest\Auth\Domain\Model\User;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserId;
use Mossetc\TechnicalTest\Auth\Domain\Service\UserAuthorizationService;
use Mossetc\TechnicalTest\Auth\Domain\Service\UserUpdateInputValidatorService;
use Mossetc\TechnicalTest\Auth\Infrastructure\Jwt\JwtAuthMiddleware;
use Mossetc\TechnicalTest\Auth\Presentation\Controller\UpdateUserController;
use Mossetc\TechnicalTest\Auth\Domain\Service\TokenServiceInterface;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Request;
use Mossetc\TechnicalTest\Tests\Support\InMemoryUserRepository;
use PHPUnit\Framework\TestCase;

final class UpdateUserControllerTest extends TestCase
{
    private const string COMPANY_A = '11111111-1111-4111-8111-111111111111';
    private const string SHOP_A    = 'aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa';

    private UserId $callerId;
    private UserId $targetId;
    private InMemoryUserRepository $userRepo;

    protected function setUp(): void
    {
        $this->callerId = UserId::generate();
        $this->targetId = UserId::generate();
        $this->userRepo = new InMemoryUserRepository();
    }

    private function makeAuth(): JwtAuthMiddleware
    {
        $svc = self::createStub(TokenServiceInterface::class);
        $svc->method('validate')->willReturn($this->callerId);

        return new JwtAuthMiddleware($svc);
    }

    private function seedUser(UserId $id, Role $role, ?string $companyId = null, ?string $shopId = null): void
    {
        $this->userRepo->save(new User(
            id: $id,
            email: new Email($id->value . '@test.test'),
            password: HashedPassword::fromPlain(new PlainPassword('password123')),
            firstName: new FirstName('Test'),
            lastName: new LastName('User'),
            role: $role,
            companyId: $companyId,
            shopId: $shopId,
        ));
    }

    private function ctrl(): UpdateUserController
    {
        return new UpdateUserController(
            $this->makeAuth(),
            new UserUpdateInputValidatorService(PhoneNumberUtil::getInstance()),
            new UpdateUserHandler($this->userRepo, new UserAuthorizationService($this->userRepo)),
        );
    }

    /** @param array<string, mixed> $body */
    private function patchRequest(array $body, ?string $targetId = null): Request
    {
        $id = $targetId ?? $this->targetId->value;

        return new Request(
            'PATCH',
            "/api/users/{$id}",
            ['Authorization' => 'Bearer tok'],
            $body,
            ['id' => $id],
        );
    }

    public function testSelfUpdateReturns200(): void
    {
        $this->seedUser($this->callerId, Role::Employee, self::COMPANY_A);
        // caller == target
        $response = $this->ctrl()($this->patchRequest(['first_name' => 'Jane'], $this->callerId->value));

        self::assertSame(200, $response->status());
    }

    public function testAdminUpdateReturns200(): void
    {
        $this->seedUser($this->callerId, Role::Admin);
        $this->seedUser($this->targetId, Role::Employee, self::COMPANY_A);

        $response = $this->ctrl()($this->patchRequest(['first_name' => 'Jane']));

        self::assertSame(200, $response->status());
    }

    public function testShopManagerCanUpdateEmployeeInOwnShop(): void
    {
        $this->seedUser($this->callerId, Role::ShopManager, self::COMPANY_A, self::SHOP_A);
        $this->seedUser($this->targetId, Role::Employee, self::COMPANY_A, self::SHOP_A);

        $response = $this->ctrl()($this->patchRequest(['first_name' => 'Jane']));

        self::assertSame(200, $response->status());
    }

    public function testReturns401WhenTokenMissing(): void
    {
        $request = new Request(
            'PATCH',
            "/api/users/{$this->targetId->value}",
            [], // no Authorization header
            ['first_name' => 'Jane'],
            ['id' => $this->targetId->value],
        );

        $response = $this->ctrl()($request);

        self::assertSame(401, $response->status());
    }

    public function testReturns401WhenTokenInvalid(): void
    {
        $svc = self::createStub(TokenServiceInterface::class);
        $svc->method('validate')->willThrowException(new InvalidTokenException('bad token'));

        $ctrl = new UpdateUserController(
            new JwtAuthMiddleware($svc),
            new UserUpdateInputValidatorService(PhoneNumberUtil::getInstance()),
            new UpdateUserHandler($this->userRepo, new UserAuthorizationService($this->userRepo)),
        );

        $response = $ctrl($this->patchRequest(['first_name' => 'Jane']));

        self::assertSame(401, $response->status());
    }

    public function testReturns403WhenEmployeeUpdatesOtherUser(): void
    {
        $this->seedUser($this->callerId, Role::Employee, self::COMPANY_A);
        $this->seedUser($this->targetId, Role::Employee, self::COMPANY_A);

        $response = $this->ctrl()($this->patchRequest(['first_name' => 'Jane']));

        self::assertSame(403, $response->status());
    }

    public function testReturns404WhenTargetNotFound(): void
    {
        $this->seedUser($this->callerId, Role::Admin);
        // targetId is not seeded

        $response = $this->ctrl()($this->patchRequest(['first_name' => 'Jane']));

        self::assertSame(404, $response->status());
    }

    public function testReturns409OnEmailConflict(): void
    {
        $otherId = UserId::generate();
        $this->userRepo->save(new User(
            id: $otherId,
            email: new Email('taken@example.com'),
            password: HashedPassword::fromPlain(new PlainPassword('password123')),
            firstName: new FirstName('Other'),
            lastName: new LastName('User'),
            role: Role::Employee,
        ));
        $this->seedUser($this->callerId, Role::Employee, self::COMPANY_A);

        // Self-update attempting to take another user's email
        $response = $this->ctrl()($this->patchRequest(
            ['email' => 'taken@example.com'],
            $this->callerId->value,
        ));

        self::assertSame(409, $response->status());
    }

    public function testReturns422OnInvalidEmail(): void
    {
        $response = $this->ctrl()($this->patchRequest(['email' => 'not-an-email']));

        self::assertSame(422, $response->status());
    }

    public function testReturns422WhenCurrentPasswordMissingOnSelfPasswordChange(): void
    {
        $this->seedUser($this->callerId, Role::Employee, self::COMPANY_A);

        $response = $this->ctrl()($this->patchRequest(
            ['password' => 'newPassword1'],
            $this->callerId->value,
        ));

        self::assertSame(422, $response->status());
    }

    public function testReturns422WhenCurrentPasswordWrong(): void
    {
        $this->seedUser($this->callerId, Role::Employee, self::COMPANY_A);

        $response = $this->ctrl()($this->patchRequest(
            ['password' => 'newPassword1', 'current_password' => 'wrongPassword123'],
            $this->callerId->value,
        ));

        self::assertSame(422, $response->status());
    }
}
