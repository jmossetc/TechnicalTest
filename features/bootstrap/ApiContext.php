<?php

declare(strict_types=1);

use Behat\Behat\Context\Context;
use Mossetc\TechnicalTest\Auth\Infrastructure\DI\ContainerFactory;
use Mossetc\TechnicalTest\Auth\Infrastructure\Http\Request;
use Mossetc\TechnicalTest\Auth\Infrastructure\Http\Response;
use Mossetc\TechnicalTest\Auth\Infrastructure\Http\Router;
use PHPUnit\Framework\Assert;

final class ApiContext implements Context
{
    private Router $router;

    private ?Response $lastResponse = null;

    private string $lastUserId = '';

    private string $token = '';

    public function __construct()
    {
        $container = ContainerFactory::buildForTest(new InMemoryUserRepository());

        $router = $container->get(Router::class);
        if (!$router instanceof Router) {
            throw new \RuntimeException('Router service not found in container');
        }

        $this->router = $router;
    }

    // ── Given ─────────────────────────────────────────────────────────────────

    /**
     * @Given a user is registered with email :email and password :password
     */
    public function aUserIsRegisteredWithEmailAndPassword(string $email, string $password): void
    {
        $response = $this->doPost('/api/users', ['email' => $email, 'password' => $password]);

        Assert::assertSame(201, $response->status(), 'Registration failed unexpectedly');

        $id = $this->bodyOf($response)['id'] ?? null;
        $this->lastUserId = is_string($id) ? $id : '';
    }

    /**
     * @Given I am logged in as :email with password :password
     */
    public function iAmLoggedInAs(string $email, string $password): void
    {
        $this->aUserIsRegisteredWithEmailAndPassword($email, $password);

        $response = $this->doPost('/api/auth/login', ['email' => $email, 'password' => $password]);

        Assert::assertSame(200, $response->status(), 'Login failed unexpectedly');

        $token = $this->bodyOf($response)['token'] ?? null;
        $this->token = is_string($token) ? $token : '';
    }

    // ── When ──────────────────────────────────────────────────────────────────

    /**
     * @When I register with email :email and password :password
     */
    public function iRegisterWithEmailAndPassword(string $email, string $password): void
    {
        $this->lastResponse = $this->doPost('/api/users', ['email' => $email, 'password' => $password]);

        $id = $this->bodyOf($this->lastResponse)['id'] ?? null;
        if (is_string($id)) {
            $this->lastUserId = $id;
        }
    }

    /**
     * @When I log in with email :email and password :password
     */
    public function iLogInWithEmailAndPassword(string $email, string $password): void
    {
        $this->lastResponse = $this->doPost('/api/auth/login', ['email' => $email, 'password' => $password]);

        $token = $this->bodyOf($this->lastResponse)['token'] ?? null;
        if (is_string($token)) {
            $this->token = $token;
        }
    }

    /**
     * @When I fetch the registered user profile without a token
     */
    public function iFetchTheRegisteredUserProfileWithoutAToken(): void
    {
        $this->lastResponse = $this->doGet("/api/users/{$this->lastUserId}");
    }

    /**
     * @When I fetch my profile
     */
    public function iFetchMyProfile(): void
    {
        $this->lastResponse = $this->doGet("/api/users/{$this->lastUserId}", $this->token);
    }

    /**
     * @When I fetch the user with id :id
     */
    public function iFetchTheUserWithId(string $id): void
    {
        $this->lastResponse = $this->doGet("/api/users/{$id}", $this->token);
    }

    // ── Then ──────────────────────────────────────────────────────────────────

    /**
     * @Then the response status should be :status
     */
    public function theResponseStatusShouldBe(int $status): void
    {
        Assert::assertNotNull($this->lastResponse, 'No request has been made yet');
        Assert::assertSame($status, $this->lastResponse->status());
    }

    /**
     * @Then the response should contain field :field
     */
    public function theResponseShouldContainField(string $field): void
    {
        $body = $this->bodyOf($this->response());
        Assert::assertArrayHasKey($field, $body, "Response body is missing field '{$field}'");
        Assert::assertNotEmpty($body[$field], "Response field '{$field}' is empty");
    }

    /**
     * @Then the response field :field should equal :value
     */
    public function theResponseFieldShouldEqual(string $field, string $value): void
    {
        $body = $this->bodyOf($this->response());
        Assert::assertArrayHasKey($field, $body, "Response body is missing field '{$field}'");
        Assert::assertSame($value, $body[$field]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /** @param array<string, mixed> $body */
    private function doPost(string $path, array $body): Response
    {
        return $this->router->dispatch(new Request(
            method: 'POST',
            path: $path,
            headers: ['Content-Type' => 'application/json'],
            body: $body,
        ));
    }

    private function doGet(string $path, string $token = ''): Response
    {
        $headers = $token !== '' ? ['Authorization' => "Bearer {$token}"] : [];

        return $this->router->dispatch(new Request(
            method: 'GET',
            path: $path,
            headers: $headers,
            body: [],
        ));
    }

    /** @return array<string, mixed> */
    private function bodyOf(Response $response): array
    {
        $data = $response->data();

        return is_array($data) ? $data : [];
    }

    private function response(): Response
    {
        Assert::assertNotNull($this->lastResponse, 'No request has been made yet');

        return $this->lastResponse;
    }
}
