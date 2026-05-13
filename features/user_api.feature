Feature: User API
  As an API consumer
  I want to register, authenticate and retrieve users

  # ── Registration ─────────────────────────────────────────────────────────────

  Scenario: Successfully registering a new user
    When I register with email "alice@example.com" and password "secretpass"
    Then the response status should be 201
    And the response should contain field "id"

  Scenario: Registering with an already taken email
    Given a user is registered with email "alice@example.com" and password "secretpass"
    When I register with email "alice@example.com" and password "secretpass"
    Then the response status should be 409

  Scenario: Registering with an invalid email format
    When I register with email "not-an-email" and password "secretpass"
    Then the response status should be 422

  Scenario: Registering with a password that is too short
    When I register with email "alice@example.com" and password "short"
    Then the response status should be 422

  Scenario: Registering without providing an email
    When I register with email "" and password "secretpass"
    Then the response status should be 422

  # ── Login ─────────────────────────────────────────────────────────────────────

  Scenario: Logging in with valid credentials returns a token
    Given a user is registered with email "alice@example.com" and password "secretpass"
    When I log in with email "alice@example.com" and password "secretpass"
    Then the response status should be 200
    And the response should contain field "token"

  Scenario: Logging in with an unknown email
    When I log in with email "ghost@example.com" and password "secretpass"
    Then the response status should be 401

  Scenario: Logging in with the wrong password
    Given a user is registered with email "alice@example.com" and password "secretpass"
    When I log in with email "alice@example.com" and password "wrongpasss"
    Then the response status should be 401

  # ── Profile ───────────────────────────────────────────────────────────────────

  Scenario: Fetching a user profile without a token
    Given a user is registered with email "alice@example.com" and password "secretpass"
    When I fetch the registered user profile without a token
    Then the response status should be 401

  Scenario: Fetching a user profile with a valid token
    Given I am logged in as "alice@example.com" with password "secretpass"
    When I fetch my profile
    Then the response status should be 200
    And the response field "email" should equal "alice@example.com"

  Scenario: Fetching a non-existent user
    Given I am logged in as "alice@example.com" with password "secretpass"
    When I fetch the user with id "550e8400-e29b-41d4-a716-446655440000"
    Then the response status should be 404

  Scenario: Fetching a user with a malformed id
    Given I am logged in as "alice@example.com" with password "secretpass"
    When I fetch the user with id "not-a-uuid"
    Then the response status should be 400

  # ── Listing ───────────────────────────────────────────────────────────────────

  Scenario: Listing users without a token
    When I list users
    Then the response status should be 401

  Scenario: Listing users returns data and pagination
    Given a user is registered with email "alice@example.com" and password "secretpass"
    And a user is registered with email "bob@example.com" and password "secretpass"
    And I am logged in as "alice@example.com" with password "secretpass"
    When I list users
    Then the response status should be 200
    And the response should contain field "data"
    And the response should contain field "pagination"
    And the "data" array should have 2 items
    And the response "pagination.total" should equal "2"

  Scenario: Listing users respects pagination limit
    Given a user is registered with email "alice@example.com" and password "secretpass"
    And a user is registered with email "bob@example.com" and password "secretpass"
    And a user is registered with email "charlie@example.com" and password "secretpass"
    And I am logged in as "alice@example.com" with password "secretpass"
    When I list users on page 1 with limit 2
    Then the response status should be 200
    And the "data" array should have 2 items
    And the response "pagination.total" should equal "3"
    And the response "pagination.pages" should equal "2"

  Scenario: Listing users second page returns remainder
    Given a user is registered with email "alice@example.com" and password "secretpass"
    And a user is registered with email "bob@example.com" and password "secretpass"
    And a user is registered with email "charlie@example.com" and password "secretpass"
    And I am logged in as "alice@example.com" with password "secretpass"
    When I list users on page 2 with limit 2
    Then the response status should be 200
    And the "data" array should have 1 item

  Scenario: Listing users is ordered by email
    Given a user is registered with email "charlie@example.com" and password "secretpass"
    And a user is registered with email "alice@example.com" and password "secretpass"
    And I am logged in as "charlie@example.com" with password "secretpass"
    When I list users
    Then the response status should be 200
    And the first "data" item "email" should equal "alice@example.com"
