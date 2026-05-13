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
use Mossetc\TechnicalTest\Auth\Domain\Service\TokenServiceInterface;
use Mossetc\TechnicalTest\Auth\Domain\Service\UserAuthorization;
use Mossetc\TechnicalTest\Auth\Domain\Service\UserDeletion;
use Mossetc\TechnicalTest\Auth\Infrastructure\Jwt\JwtAuthMiddleware;
use Mossetc\TechnicalTest\Auth\Presentation\Controller\DeleteUserController;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Request;
use Mossetc\TechnicalTest\Tests\Support\InMemoryUserRepository;
use PHPUnit\Framework\TestCase;

final class DeleteUserControllerTest extends TestCase
{
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
        $svc = $this->createStub(TokenServiceInterface::class);
        $svc->method('validate')->willReturn($this->callerId);

        return new JwtAuthMiddleware($svc);
    }

    private function seedUser(UserId $id, Role $role, ?string $companyId = null): void
    {
        $this->userRepo->save(new User(
            id:        $id,
            email:     new Email($id->value . '@test.test'),
            password:  HashedPassword::fromPlain(new PlainPassword('password123')),
            firstName: new FirstName('Test'),
            lastName:  new LastName('User'),
            role:      $role,
            companyId: $companyId,
        ));
    }

    private function ctrl(): DeleteUserController
    {
        return new DeleteUserController(
            $this->makeAuth(),
            $this->userRepo,
            new UserDeletion($this->userRepo, new UserAuthorization($this->userRepo)),
        );
    }

    private function deleteRequest(): Request
    {
        return new Request(
            'DELETE', "/api/users/{$this->targetId->value}",
            ['Authorization' => 'Bearer tok'], [], ['id' => $this->targetId->value],
        );
    }

    public function testReturnsDeletedTrueOnSuccess(): void
    {
        $this->seedUser($this->callerId, Role::Admin);
        $this->seedUser($this->targetId, Role::Employee);

        $response = $this->ctrl()($this->deleteRequest());

        $this->assertSame(200, $response->status());
        $this->assertTrue($response->data()['deleted'] ?? false);
    }

    public function testSoftDeletesTargetUser(): void
    {
        $this->seedUser($this->callerId, Role::Admin);
        $this->seedUser($this->targetId, Role::Employee);

        $this->ctrl()($this->deleteRequest());

        $this->assertNull($this->userRepo->findById($this->targetId));
    }

    public function testReturns401WhenNoAuthorizationHeader(): void
    {
        $response = $this->ctrl()(new Request(
            'DELETE', "/api/users/{$this->targetId->value}", [], [], ['id' => $this->targetId->value],
        ));

        $this->assertSame(401, $response->status());
    }

    public function testReturns422ForMalformedId(): void
    {
        $this->seedUser($this->callerId, Role::Admin);

        $response = $this->ctrl()(new Request(
            'DELETE', '/api/users/bad', ['Authorization' => 'Bearer tok'], [], ['id' => 'bad'],
        ));

        $this->assertSame(422, $response->status());
    }

    public function testReturns404WhenTargetNotFound(): void
    {
        $this->seedUser($this->callerId, Role::Admin);

        $response = $this->ctrl()($this->deleteRequest());

        $this->assertSame(404, $response->status());
    }

    public function testReturns403WhenForbidden(): void
    {
        $this->seedUser($this->callerId, Role::Employee);
        $this->seedUser($this->targetId, Role::Employee);

        $response = $this->ctrl()($this->deleteRequest());

        $this->assertSame(403, $response->status());
    }
}
