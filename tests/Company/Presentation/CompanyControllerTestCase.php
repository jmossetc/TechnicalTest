<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Company\Presentation;

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
use Mossetc\TechnicalTest\Company\Application\Command\CreateCompany;
use Mossetc\TechnicalTest\Company\Application\Handler\CreateCompanyHandler;
use Mossetc\TechnicalTest\Company\Domain\Model\CompanyId;
use Mossetc\TechnicalTest\Tests\Support\InMemoryCompanyRepository;
use PHPUnit\Framework\TestCase;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Request;

abstract class CompanyControllerTestCase extends TestCase
{
    protected UserId $callerId;
    protected InMemoryCompanyRepository $companyRepo;

    protected function setUp(): void
    {
        $this->callerId    = UserId::generate();
        $this->companyRepo = new InMemoryCompanyRepository();
    }

    protected function makeAuth(): JwtAuthMiddleware
    {
        $svc = $this->createStub(TokenServiceInterface::class);
        $svc->method('validate')->willReturn($this->callerId);

        return new JwtAuthMiddleware($svc);
    }

    protected function makeAuthzAsAdmin(): UserAuthorizationService
    {
        return $this->makeAuthzWithRole(Role::Admin);
    }

    protected function makeAuthzWithRole(Role $role, ?string $companyId = null): UserAuthorizationService
    {
        $caller = new User(
            id:        $this->callerId,
            email:     new Email('caller@test.test'),
            password:  HashedPassword::fromPlain(new PlainPassword('password123')),
            firstName: new FirstName('Caller'),
            lastName:  new LastName('User'),
            role:      $role,
            companyId: $companyId,
        );

        $repo = $this->createStub(UserRepositoryInterface::class);
        $repo->method('findById')->willReturn($caller);

        return new UserAuthorizationService($repo);
    }

    protected function seedCompany(string $name = 'Test Company'): CompanyId
    {
        return (new CreateCompanyHandler($this->companyRepo))->handle(new CreateCompany($name));
    }

    /**
     * @param array<string, mixed>  $body
     * @param array<string, string> $attrs
     * @param array<string, string> $query
     */
    protected function authedRequest(string $method, string $path, array $body = [], array $attrs = [], array $query = []): Request
    {
        return new Request($method, $path, ['Authorization' => 'Bearer tok'], $body, $attrs, $query);
    }

    /** @param array<string, string> $attrs */
    protected function unauthRequest(string $method, string $path, array $attrs = []): Request
    {
        return new Request($method, $path, [], [], $attrs);
    }
}
