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
     * Registers the user if not already registered (409 is tolerated), then logs in.
     *
     * @Given I am logged in as :email with password :password
     */
    public function iAmLoggedInAs(string $email, string $password): void
    {
        $regResponse = $this->doPost('/api/users', ['email' => $email, 'password' => $password]);

        if ($regResponse->status() === 201) {
            $id = $this->bodyOf($regResponse)['id'] ?? null;
            $this->lastUserId = is_string($id) ? $id : '';
        }

        Assert::assertContains(
            $regResponse->status(),
            [201, 409],
            "Registration step expected 201 or 409, got {$regResponse->status()}",
        );

        $loginResponse = $this->doPost('/api/auth/login', ['email' => $email, 'password' => $password]);

        Assert::assertSame(200, $loginResponse->status(), 'Login failed unexpectedly');

        $token = $this->bodyOf($loginResponse)['token'] ?? null;
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

    /**
     * @When I list users
     */
    public function iListUsers(): void
    {
        $this->lastResponse = $this->doGet('/api/users', $this->token);
    }

    /**
     * @When I list users on page :page with limit :limit
     */
    public function iListUsersOnPageWithLimit(int $page, int $limit): void
    {
        $this->lastResponse = $this->doGet(
            '/api/users',
            $this->token,
            ['page' => (string) $page, 'limit' => (string) $limit],
        );
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

    /**
     * Supports dot-notation for nested fields, e.g. "pagination.total".
     *
     * @Then the response :path should equal :value
     */
    public function theResponsePathShouldEqual(string $path, string $value): void
    {
        $parts  = explode('.', $path);
        $cursor = $this->bodyOf($this->response());

        foreach ($parts as $part) {
            Assert::assertIsArray($cursor, "Path segment '{$part}' is not an array");
            Assert::assertArrayHasKey($part, $cursor, "Missing key '{$part}' in response");
            $cursor = $cursor[$part];
        }

        Assert::assertSame($value, (string) $cursor, "Response path '{$path}' does not equal '{$value}'");
    }

    /**
     * @Then the :field array should have :count item(s)
     */
    public function theArrayShouldHaveItems(string $field, int $count): void
    {
        $body = $this->bodyOf($this->response());
        Assert::assertArrayHasKey($field, $body, "Response body is missing field '{$field}'");
        Assert::assertIsArray($body[$field], "'{$field}' is not an array");
        Assert::assertCount($count, $body[$field]);
    }

    /**
     * @Then the first :field item :key should equal :value
     */
    public function theFirstArrayItemKeyShouldEqual(string $field, string $key, string $value): void
    {
        $body = $this->bodyOf($this->response());
        Assert::assertArrayHasKey($field, $body);
        Assert::assertIsArray($body[$field]);
        Assert::assertNotEmpty($body[$field], "'{$field}' array is empty");

        $first = $body[$field][0] ?? null;
        Assert::assertIsArray($first);
        Assert::assertArrayHasKey($key, $first);
        Assert::assertSame($value, $first[$key]);
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

    /**
     * @param array<string, string> $query
     */
    private function doGet(string $path, string $token = '', array $query = []): Response
    {
        $headers = $token !== '' ? ['Authorization' => "Bearer {$token}"] : [];

        return $this->router->dispatch(new Request(
            method: 'GET',
            path: $path,
            headers: $headers,
            body: [],
            query: $query,
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
