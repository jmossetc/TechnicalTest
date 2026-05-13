<?php

declare(strict_types=1);

use Behat\Behat\Context\Context;
use Mossetc\TechnicalTest\Auth\Domain\Model\Role;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserId;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserRole;
use Mossetc\TechnicalTest\Company\Domain\Model\Company;
use Mossetc\TechnicalTest\Company\Domain\Model\CompanyId;
use Mossetc\TechnicalTest\Company\Domain\Model\CompanyName;
use Mossetc\TechnicalTest\Shared\Infrastructure\DI\ContainerFactory;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Request;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Response;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Router;
use PHPUnit\Framework\Assert;

final class ApiContext implements Context
{
    private Router $router;

    private InMemoryUserRepository $userRepository;

    private InMemoryUserRoleRepository $roleRepository;

    private InMemoryCompanyRepository $companyRepository;

    private ?Response $lastResponse = null;

    private string $lastUserId = '';

    private string $token = '';

    /** ID of a target user created for deletion tests. */
    private string $targetUserId = '';

    /** Token for a bootstrapped admin user, used for registration calls. */
    private string $adminToken = '';

    /** ID of the last created company, for assertions. */
    private string $lastCompanyId = '';

    public function __construct()
    {
        $this->userRepository    = new InMemoryUserRepository();
        $this->roleRepository    = new InMemoryUserRoleRepository();
        $this->companyRepository = new InMemoryCompanyRepository();
        $container = ContainerFactory::buildForTest(
            $this->userRepository,
            $this->roleRepository,
            $this->companyRepository,
        );

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
        $this->ensureAdminToken();

        $response = $this->doPost('/api/users', ['email' => $email, 'password' => $password], $this->adminToken);

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
        $this->ensureAdminToken();

        $regResponse = $this->doPost('/api/users', ['email' => $email, 'password' => $password], $this->adminToken);

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

    /**
     * @Given a target user exists with role company_manager for company :companyId
     */
    public function aTargetUserExistsWithRoleCompanyManagerForCompany(string $companyId): void
    {
        $userId = $this->seedUser("target-cm-{$companyId}@internal.test", 'Target1234!');
        $this->roleRepository->grantRole($userId, new UserRole(Role::CompanyManager, companyId: $companyId));
        $this->targetUserId = $userId->value;
    }

    /**
     * @Given a target user exists with role shop_manager for shop :shopId
     */
    public function aTargetUserExistsWithRoleShopManagerForShop(string $shopId): void
    {
        $userId = $this->seedUser("target-sm-{$shopId}@internal.test", 'Target1234!');
        $this->roleRepository->grantRole($userId, new UserRole(Role::ShopManager, shopId: $shopId));
        $this->targetUserId = $userId->value;
    }

    /**
     * @Given a target user exists with role admin
     */
    public function aTargetUserExistsWithRoleAdmin(): void
    {
        $userId = $this->seedUser('target-admin@internal.test', 'Target1234!');
        $this->roleRepository->grantRole($userId, new UserRole(Role::Admin));
        $this->targetUserId = $userId->value;
    }

    /**
     * @Given a target user exists with no role
     */
    public function aTargetUserExistsWithNoRole(): void
    {
        $userId = $this->seedUser('target-norole@internal.test', 'Target1234!');
        $this->targetUserId = $userId->value;
    }

    /**
     * @Given a company :companyId exists
     */
    public function aCompanyExists(string $companyId): void
    {
        $this->companyRepository->save(new Company(
            id: new CompanyId($companyId),
            name: new CompanyName("Company {$companyId}"),
        ));
    }

    /**
     * @Given a shop :shopId exists for company :companyId
     */
    public function aShopExistsForCompany(string $shopId, string $companyId): void
    {
        $this->roleRepository->registerShop($shopId, $companyId);
    }

    /**
     * @Given I am logged in as admin
     */
    public function iAmLoggedInAsAdmin(): void
    {
        $this->ensureAdminToken();
        $this->token = $this->adminToken;
    }

    /**
     * @Given I am logged in as company manager of :companyId
     */
    public function iAmLoggedInAsCompanyManagerOf(string $companyId): void
    {
        $email    = "cm-{$companyId}@internal.test";
        $password = 'Manager1234!';

        $userId = $this->seedUser($email, $password);
        $this->roleRepository->grantRole($userId, new UserRole(Role::CompanyManager, companyId: $companyId));

        $this->token = $this->loginAndGetToken($email, $password);
    }

    /**
     * @Given I am logged in as shop manager of :shopId
     */
    public function iAmLoggedInAsShopManagerOf(string $shopId): void
    {
        $email    = "sm-{$shopId}@internal.test";
        $password = 'Manager1234!';

        $userId = $this->seedUser($email, $password);
        $this->roleRepository->grantRole($userId, new UserRole(Role::ShopManager, shopId: $shopId));

        $this->token = $this->loginAndGetToken($email, $password);
    }

    /**
     * @Given I am logged in as a user with no role
     */
    public function iAmLoggedInAsAUserWithNoRole(): void
    {
        $email    = 'norole@internal.test';
        $password = 'NoRole1234!';

        $this->seedUser($email, $password);
        $this->token = $this->loginAndGetToken($email, $password);
    }

    // ── When ──────────────────────────────────────────────────────────────────

    /**
     * @When I register without authentication with email :email and password :password
     */
    public function iRegisterWithoutAuthenticationWithEmailAndPassword(string $email, string $password): void
    {
        $this->lastResponse = $this->doPost('/api/users', ['email' => $email, 'password' => $password]);
    }

    /**
     * @When I register with email :email and password :password
     */
    public function iRegisterWithEmailAndPassword(string $email, string $password): void
    {
        $this->ensureAdminToken();

        $this->lastResponse = $this->doPost('/api/users', ['email' => $email, 'password' => $password], $this->adminToken);

        $id = $this->bodyOf($this->lastResponse)['id'] ?? null;
        if (is_string($id)) {
            $this->lastUserId = $id;
        }
    }

    /**
     * @When I register with current token email :email and password :password
     */
    public function iRegisterWithCurrentTokenEmailAndPassword(string $email, string $password): void
    {
        $this->lastResponse = $this->doPost('/api/users', ['email' => $email, 'password' => $password], $this->token);

        $id = $this->bodyOf($this->lastResponse)['id'] ?? null;
        if (is_string($id)) {
            $this->lastUserId = $id;
        }
    }

    /**
     * @When I register a user with email :email and password :password with role :role
     */
    public function iRegisterAUserWithRole(string $email, string $password, string $role): void
    {
        $this->lastResponse = $this->doPost('/api/users', [
            'email'    => $email,
            'password' => $password,
            'role'     => $role,
        ], $this->token);

        $id = $this->bodyOf($this->lastResponse)['id'] ?? null;
        if (is_string($id)) {
            $this->lastUserId = $id;
        }
    }

    /**
     * @Given a user is registered with email :email and password :password with role :role for company :companyId
     */
    public function aUserIsRegisteredWithRoleForCompany(string $email, string $password, string $role, string $companyId): void
    {
        $this->ensureAdminToken();
        $response = $this->doPost('/api/users', [
            'email'      => $email,
            'password'   => $password,
            'role'       => $role,
            'company_id' => $companyId,
        ], $this->adminToken);

        Assert::assertSame(201, $response->status(), "Background registration of {$email} (company) failed");
    }

    /**
     * @Given a user is registered with email :email and password :password with role :role for shop :shopId
     */
    public function aUserIsRegisteredWithRoleForShop(string $email, string $password, string $role, string $shopId): void
    {
        $this->ensureAdminToken();
        $response = $this->doPost('/api/users', [
            'email'    => $email,
            'password' => $password,
            'role'     => $role,
            'shop_id'  => $shopId,
        ], $this->adminToken);

        Assert::assertSame(201, $response->status(), "Background registration of {$email} (shop) failed");
    }

    /**
     * @When I register a user with email :email and password :password with role :role for company :companyId
     */
    public function iRegisterAUserWithRoleForCompany(string $email, string $password, string $role, string $companyId): void
    {
        $this->lastResponse = $this->doPost('/api/users', [
            'email'      => $email,
            'password'   => $password,
            'role'       => $role,
            'company_id' => $companyId,
        ], $this->token);

        $id = $this->bodyOf($this->lastResponse)['id'] ?? null;
        if (is_string($id)) {
            $this->lastUserId = $id;
        }
    }

    /**
     * @When I register a user with email :email and password :password with role :role for shop :shopId
     */
    public function iRegisterAUserWithRoleForShop(string $email, string $password, string $role, string $shopId): void
    {
        $this->lastResponse = $this->doPost('/api/users', [
            'email'    => $email,
            'password' => $password,
            'role'     => $role,
            'shop_id'  => $shopId,
        ], $this->token);

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

    /**
     * @When I list users filtered by company :companyId
     */
    public function iListUsersFilteredByCompany(string $companyId): void
    {
        $this->lastResponse = $this->doGet('/api/users', $this->token, ['company_ids' => $companyId]);
    }

    /**
     * @When I list users filtered by shop :shopId
     */
    public function iListUsersFilteredByShop(string $shopId): void
    {
        $this->lastResponse = $this->doGet('/api/users', $this->token, ['shop_ids' => $shopId]);
    }

    /**
     * @When I delete the target user
     */
    public function iDeleteTheTargetUser(): void
    {
        $this->lastResponse = $this->doDelete("/api/users/{$this->targetUserId}", $this->token);
    }

    /**
     * @When I delete user :userId
     */
    public function iDeleteUser(string $userId): void
    {
        $this->lastResponse = $this->doDelete("/api/users/{$userId}", $this->token);
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

    // ── Company When ─────────────────────────────────────────────────────────

    /**
     * @When I create a company with name :name
     */
    public function iCreateACompanyWithName(string $name): void
    {
        $this->lastResponse = $this->doPost('/api/companies', ['name' => $name], $this->token);

        $id = $this->bodyOf($this->lastResponse)['id'] ?? null;
        if (is_string($id)) {
            $this->lastCompanyId = $id;
        }
    }

    /**
     * @When I list companies
     */
    public function iListCompanies(): void
    {
        $this->lastResponse = $this->doGet('/api/companies', $this->token);
    }

    /**
     * @When I list companies on page :page with limit :limit
     */
    public function iListCompaniesOnPageWithLimit(int $page, int $limit): void
    {
        $this->lastResponse = $this->doGet(
            '/api/companies',
            $this->token,
            ['page' => (string) $page, 'limit' => (string) $limit],
        );
    }

    /**
     * @When I get the company :companyId
     */
    public function iGetTheCompany(string $companyId): void
    {
        $this->lastResponse = $this->doGet("/api/companies/{$companyId}", $this->token);
    }

    /**
     * @When I get the last created company
     */
    public function iGetTheLastCreatedCompany(): void
    {
        $this->lastResponse = $this->doGet("/api/companies/{$this->lastCompanyId}", $this->token);
    }

    /**
     * @When I update the company :companyId with name :name
     */
    public function iUpdateTheCompanyWithName(string $companyId, string $name): void
    {
        $this->lastResponse = $this->doPatch("/api/companies/{$companyId}", ['name' => $name], $this->token);
    }

    /**
     * @When I update the last created company with name :name
     */
    public function iUpdateTheLastCreatedCompanyWithName(string $name): void
    {
        $this->lastResponse = $this->doPatch("/api/companies/{$this->lastCompanyId}", ['name' => $name], $this->token);
    }

    /**
     * @When I delete the company :companyId
     */
    public function iDeleteTheCompany(string $companyId): void
    {
        $this->lastResponse = $this->doDelete("/api/companies/{$companyId}", $this->token);
    }

    /**
     * @When I delete the last created company
     */
    public function iDeleteTheLastCreatedCompany(): void
    {
        $this->lastResponse = $this->doDelete("/api/companies/{$this->lastCompanyId}", $this->token);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /** @param array<string, mixed> $body */
    private function doPost(string $path, array $body, string $token = ''): Response
    {
        $headers = ['Content-Type' => 'application/json'];
        if ($token !== '') {
            $headers['Authorization'] = "Bearer {$token}";
        }

        return $this->router->dispatch(new Request(
            method: 'POST',
            path: $path,
            headers: $headers,
            body: $body,
        ));
    }

    /**
     * Bootstraps an admin user by directly seeding the in-memory repositories
     * and obtains a JWT token via the login endpoint.
     */
    private function ensureAdminToken(): void
    {
        if ($this->adminToken !== '') {
            return;
        }

        $adminEmail    = 'behat-admin@internal.test';
        $adminPassword = 'Admin1234!';

        // Seed the admin user directly into the in-memory stores
        $userId = UserId::generate();
        $user = new \Mossetc\TechnicalTest\Auth\Domain\Model\User(
            id: $userId,
            email: new \Mossetc\TechnicalTest\Auth\Domain\Model\Email($adminEmail),
            password: \Mossetc\TechnicalTest\Auth\Domain\Model\HashedPassword::fromPlain(
                new \Mossetc\TechnicalTest\Auth\Domain\Model\PlainPassword($adminPassword),
            ),
        );

        $this->userRepository->save($user);
        $this->roleRepository->grantRole($userId, new UserRole(Role::Admin));

        // Log in to obtain a JWT
        $loginResponse = $this->doPost('/api/auth/login', ['email' => $adminEmail, 'password' => $adminPassword]);
        Assert::assertSame(200, $loginResponse->status(), 'Admin login failed');

        $token = $this->bodyOf($loginResponse)['token'] ?? null;
        $this->adminToken = is_string($token) ? $token : '';
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

    /** @param array<string, mixed> $body */
    private function doPatch(string $path, array $body, string $token = ''): Response
    {
        $headers = ['Content-Type' => 'application/json'];
        if ($token !== '') {
            $headers['Authorization'] = "Bearer {$token}";
        }

        return $this->router->dispatch(new Request(
            method: 'PATCH',
            path: $path,
            headers: $headers,
            body: $body,
        ));
    }

    private function doDelete(string $path, string $token = ''): Response
    {
        $headers = $token !== '' ? ['Authorization' => "Bearer {$token}"] : [];

        return $this->router->dispatch(new Request(
            method: 'DELETE',
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

    /**
     * Seeds a user directly into the in-memory repository and returns the UserId.
     */
    private function seedUser(string $email, string $password): UserId
    {
        $userId = UserId::generate();
        $user = new \Mossetc\TechnicalTest\Auth\Domain\Model\User(
            id: $userId,
            email: new \Mossetc\TechnicalTest\Auth\Domain\Model\Email($email),
            password: \Mossetc\TechnicalTest\Auth\Domain\Model\HashedPassword::fromPlain(
                new \Mossetc\TechnicalTest\Auth\Domain\Model\PlainPassword($password),
            ),
        );

        $this->userRepository->save($user);

        return $userId;
    }

    /**
     * Logs in via the HTTP layer and returns the JWT token.
     */
    private function loginAndGetToken(string $email, string $password): string
    {
        $response = $this->doPost('/api/auth/login', ['email' => $email, 'password' => $password]);
        Assert::assertSame(200, $response->status(), "Login failed for {$email}");

        $token = $this->bodyOf($response)['token'] ?? null;

        return is_string($token) ? $token : '';
    }
}
