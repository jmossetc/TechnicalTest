<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Auth\Presentation\Controller;

use Mossetc\TechnicalTest\Auth\Domain\Model\Email;
use Mossetc\TechnicalTest\Auth\Domain\Model\FirstName;
use Mossetc\TechnicalTest\Auth\Domain\Model\HashedPassword;
use Mossetc\TechnicalTest\Auth\Domain\Model\LastName;
use Mossetc\TechnicalTest\Auth\Domain\Model\PlainPassword;
use Mossetc\TechnicalTest\Auth\Domain\Model\Role;
use Mossetc\TechnicalTest\Auth\Domain\Model\User;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserId;
use Mossetc\TechnicalTest\Auth\Domain\Repository\UserRepositoryInterface;
use Mossetc\TechnicalTest\Auth\Domain\Service\TokenServiceInterface;
use Mossetc\TechnicalTest\Auth\Domain\Service\UserAuthorizationService;
use Mossetc\TechnicalTest\Auth\Infrastructure\Jwt\JwtAuthMiddleware;
use Mossetc\TechnicalTest\Auth\Presentation\Controller\GetUserController;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Request;
use PHPUnit\Framework\TestCase;

final class GetUserControllerTest extends TestCase
{
    private UserId $userId;
    private User $user;

    protected function setUp(): void
    {
        $this->userId = UserId::generate();
        $this->user   = new User(
            id:        $this->userId,
            email:     new Email('alice@example.com'),
            password:  HashedPassword::fromPlain(new PlainPassword('password123')),
            firstName: new FirstName('Alice'),
            lastName:  new LastName('Smith'),
            role:      Role::Employee,
        );
    }

    private function makeAuth(?UserId $callerId = null): JwtAuthMiddleware
    {
        $svc = $this->createStub(TokenServiceInterface::class);
        $svc->method('validate')->willReturn($callerId ?? $this->userId);

        return new JwtAuthMiddleware($svc);
    }

    private function makeRepo(?User $user): UserRepositoryInterface
    {
        $repo = $this->createStub(UserRepositoryInterface::class);
        $repo->method('findById')->willReturn($user);

        return $repo;
    }

    private function makeAuthorization(User $caller): UserAuthorizationService
    {
        $repo = $this->createStub(UserRepositoryInterface::class);
        $repo->method('findById')->willReturn($caller);

        return new UserAuthorizationService($repo);
    }

    private function authedRequest(string $id): Request
    {
        return new Request('GET', "/api/users/{$id}", ['Authorization' => 'Bearer tok'], [], ['id' => $id]);
    }

    public function testReturnsUserDataOnSuccess(): void
    {
        $ctrl     = new GetUserController(
            $this->makeRepo($this->user),
            $this->makeAuth(),
            $this->makeAuthorization($this->user),
        );
        $response = $ctrl($this->authedRequest($this->userId->value));

        $this->assertSame(200, $response->status());
        $data = $response->data();
        $this->assertSame($this->userId->value, $data['id']);
        $this->assertSame('alice@example.com',  $data['email']);
        $this->assertSame('Alice',              $data['first_name']);
        $this->assertSame('Smith',              $data['last_name']);
        $this->assertSame('employee',           $data['role']);
        $this->assertTrue($data['is_active']);
    }

    public function testReturns401WhenNoAuthorizationHeader(): void
    {
        $ctrl     = new GetUserController(
            $this->makeRepo($this->user),
            $this->makeAuth(),
            $this->makeAuthorization($this->user),
        );
        $response = $ctrl(new Request('GET', "/api/users/{$this->userId->value}", [], [], ['id' => $this->userId->value]));

        $this->assertSame(401, $response->status());
    }

    public function testReturns400ForMalformedId(): void
    {
        $ctrl     = new GetUserController(
            $this->makeRepo(null),
            $this->makeAuth(),
            $this->makeAuthorization($this->user),
        );
        $response = $ctrl($this->authedRequest('not-a-uuid'));

        $this->assertSame(400, $response->status());
    }

    public function testReturns404WhenUserNotFound(): void
    {
        $ctrl     = new GetUserController(
            $this->makeRepo(null),
            $this->makeAuth(),
            $this->makeAuthorization($this->user),
        );
        $response = $ctrl($this->authedRequest('550e8400-e29b-41d4-a716-446655440000'));

        $this->assertSame(404, $response->status());
    }

    public function testReturns403WhenCallerIsNotAuthorized(): void
    {
        $targetId   = UserId::generate();
        $target     = new User(
            id:        $targetId,
            email:     new Email('bob@example.com'),
            password:  HashedPassword::fromPlain(new PlainPassword('password123')),
            firstName: new FirstName('Bob'),
            lastName:  new LastName('Jones'),
            role:      Role::Employee,
        );
        $callerId   = UserId::generate();
        $caller     = new User(
            id:        $callerId,
            email:     new Email('shopmanager@example.com'),
            password:  HashedPassword::fromPlain(new PlainPassword('password123')),
            firstName: new FirstName('Shop'),
            lastName:  new LastName('Manager'),
            role:      Role::ShopManager,
        );

        $ctrl     = new GetUserController(
            $this->makeRepo($target),
            $this->makeAuth($callerId),
            $this->makeAuthorization($caller),
        );
        $response = $ctrl($this->authedRequest($targetId->value));

        $this->assertSame(403, $response->status());
    }
}
