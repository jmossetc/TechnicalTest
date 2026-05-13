Feature: User API
  As an API consumer
  I want to register, authenticate and retrieve users

  # ── Registration ─────────────────────────────────────────────────────────────

  Scenario: Registering without authentication returns 401
    When I register without authentication with email "alice@example.com" and password "secretpass"
    Then the response status should be 401

  Scenario: Successfully registering a new user as admin
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

  # ── Role-based registration (Admin) ──────────────────────────────────────────

  Scenario: Admin creates a user with admin role
    Given I am logged in as admin
    When I register a user with email "new-admin@example.com" and password "secretpass" with role "admin"
    Then the response status should be 201
    And the response should contain field "id"

  Scenario: Admin creates a user with company_admin role
    Given I am logged in as admin
    And a company "11111111-1111-4111-8111-111111111111" exists
    When I register a user with email "cm@example.com" and password "secretpass" with role "company_admin" for company "11111111-1111-4111-8111-111111111111"
    Then the response status should be 201

  Scenario: Admin creates a user with shop_manager role
    Given I am logged in as admin
    And a company "11111111-1111-4111-8111-111111111111" exists
    And a shop "aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa" exists for company "11111111-1111-4111-8111-111111111111"
    When I register a user with email "sm@example.com" and password "secretpass" with role "shop_manager" for shop "aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa"
    Then the response status should be 201

  Scenario: Admin creates a user without any role
    Given I am logged in as admin
    When I register with email "plain@example.com" and password "secretpass"
    Then the response status should be 201

  # ── Role-based registration (Company Manager) ───────────────────────────────

  Scenario: Company manager creates a user for their company
    Given a company "11111111-1111-4111-8111-111111111111" exists
    And I am logged in as company admin of "11111111-1111-4111-8111-111111111111"
    When I register a user with email "cm2@example.com" and password "secretpass" with role "company_admin" for company "11111111-1111-4111-8111-111111111111"
    Then the response status should be 201

  Scenario: Company manager creates a shop manager for a shop of their company
    Given a company "11111111-1111-4111-8111-111111111111" exists
    And a shop "aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa" exists for company "11111111-1111-4111-8111-111111111111"
    And I am logged in as company admin of "11111111-1111-4111-8111-111111111111"
    When I register a user with email "sm2@example.com" and password "secretpass" with role "shop_manager" for shop "aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa"
    Then the response status should be 201

  Scenario: Company manager cannot create a user for another company
    Given a company "11111111-1111-4111-8111-111111111111" exists
    And a company "22222222-2222-4222-8222-222222222222" exists
    And I am logged in as company admin of "11111111-1111-4111-8111-111111111111"
    When I register a user with email "cm3@example.com" and password "secretpass" with role "company_admin" for company "22222222-2222-4222-8222-222222222222"
    Then the response status should be 403

  Scenario: Company manager cannot create a shop manager for a shop of another company
    Given a company "11111111-1111-4111-8111-111111111111" exists
    And a company "22222222-2222-4222-8222-222222222222" exists
    And a shop "bbbb1111-bbbb-4bbb-8bbb-bbbbbbbbbbbb" exists for company "22222222-2222-4222-8222-222222222222"
    And I am logged in as company admin of "11111111-1111-4111-8111-111111111111"
    When I register a user with email "sm3@example.com" and password "secretpass" with role "shop_manager" for shop "bbbb1111-bbbb-4bbb-8bbb-bbbbbbbbbbbb"
    Then the response status should be 403

  Scenario: Company manager cannot create an admin
    Given a company "11111111-1111-4111-8111-111111111111" exists
    And I am logged in as company admin of "11111111-1111-4111-8111-111111111111"
    When I register a user with email "admin2@example.com" and password "secretpass" with role "admin"
    Then the response status should be 403

  Scenario: Company manager can create a user without any role
    Given a company "11111111-1111-4111-8111-111111111111" exists
    And I am logged in as company admin of "11111111-1111-4111-8111-111111111111"
    When I register with current token email "plain2@example.com" and password "secretpass"
    Then the response status should be 201

  # ── Role-based registration (Shop Manager) ──────────────────────────────────

  Scenario: Shop manager creates a user for their shop
    Given a company "11111111-1111-4111-8111-111111111111" exists
    And a shop "aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa" exists for company "11111111-1111-4111-8111-111111111111"
    And I am logged in as shop manager of "aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa"
    When I register a user with email "sm4@example.com" and password "secretpass" with role "shop_manager" for shop "aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa"
    Then the response status should be 201

  Scenario: Shop manager cannot create a user for another shop
    Given a company "11111111-1111-4111-8111-111111111111" exists
    And a shop "aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa" exists for company "11111111-1111-4111-8111-111111111111"
    And a shop "aaaa2222-aaaa-4aaa-8aaa-aaaaaaaaaaaa" exists for company "11111111-1111-4111-8111-111111111111"
    And I am logged in as shop manager of "aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa"
    When I register a user with email "sm5@example.com" and password "secretpass" with role "shop_manager" for shop "aaaa2222-aaaa-4aaa-8aaa-aaaaaaaaaaaa"
    Then the response status should be 403

  Scenario: Shop manager cannot create a company admin
    Given a company "11111111-1111-4111-8111-111111111111" exists
    And a shop "aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa" exists for company "11111111-1111-4111-8111-111111111111"
    And I am logged in as shop manager of "aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa"
    When I register a user with email "cm4@example.com" and password "secretpass" with role "company_admin" for company "11111111-1111-4111-8111-111111111111"
    Then the response status should be 403

  Scenario: Shop manager cannot create an admin
    Given a company "11111111-1111-4111-8111-111111111111" exists
    And a shop "aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa" exists for company "11111111-1111-4111-8111-111111111111"
    And I am logged in as shop manager of "aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa"
    When I register a user with email "admin3@example.com" and password "secretpass" with role "admin"
    Then the response status should be 403

  Scenario: Shop manager can create a user without any role
    Given a company "11111111-1111-4111-8111-111111111111" exists
    And a shop "aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa" exists for company "11111111-1111-4111-8111-111111111111"
    And I am logged in as shop manager of "aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa"
    When I register with current token email "plain3@example.com" and password "secretpass"
    Then the response status should be 201

  # ── Role-based registration (No role) ───────────────────────────────────────

  Scenario: User without any role cannot create accounts
    Given I am logged in as a user with no role
    When I register with current token email "someone@example.com" and password "secretpass"
    Then the response status should be 403

  # ── Deletion (Admin) ──────────────────────────────────────────────────────────

  Scenario: Admin deletes a company admin
    Given I am logged in as admin
    And a company "11111111-1111-4111-8111-111111111111" exists
    And a target user exists with role company_admin for company "11111111-1111-4111-8111-111111111111"
    When I delete the target user
    Then the response status should be 200

  Scenario: Admin deletes a shop manager
    Given I am logged in as admin
    And a company "11111111-1111-4111-8111-111111111111" exists
    And a shop "aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa" exists for company "11111111-1111-4111-8111-111111111111"
    And a target user exists with role shop_manager for shop "aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa"
    When I delete the target user
    Then the response status should be 200

  Scenario: Admin cannot delete another admin
    Given I am logged in as admin
    And a target user exists with role admin
    When I delete the target user
    Then the response status should be 403

  Scenario: Admin deletes a user with no role
    Given I am logged in as admin
    And a target user exists with no role
    When I delete the target user
    Then the response status should be 200

  # ── Deletion (Company Manager) ──────────────────────────────────────────────

  Scenario: Company manager deletes a shop manager of their company
    Given a company "11111111-1111-4111-8111-111111111111" exists
    And a shop "aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa" exists for company "11111111-1111-4111-8111-111111111111"
    And I am logged in as company admin of "11111111-1111-4111-8111-111111111111"
    And a target user exists with role shop_manager for shop "aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa"
    When I delete the target user
    Then the response status should be 200

  Scenario: Company manager cannot delete a shop manager of another company
    Given a company "11111111-1111-4111-8111-111111111111" exists
    And a company "22222222-2222-4222-8222-222222222222" exists
    And a shop "bbbb1111-bbbb-4bbb-8bbb-bbbbbbbbbbbb" exists for company "22222222-2222-4222-8222-222222222222"
    And I am logged in as company admin of "11111111-1111-4111-8111-111111111111"
    And a target user exists with role shop_manager for shop "bbbb1111-bbbb-4bbb-8bbb-bbbbbbbbbbbb"
    When I delete the target user
    Then the response status should be 403

  Scenario: Company manager cannot delete a company admin
    Given a company "11111111-1111-4111-8111-111111111111" exists
    And I am logged in as company admin of "11111111-1111-4111-8111-111111111111"
    And a target user exists with role company_admin for company "11111111-1111-4111-8111-111111111111"
    When I delete the target user
    Then the response status should be 403

  Scenario: Company manager cannot delete an admin
    Given a company "11111111-1111-4111-8111-111111111111" exists
    And I am logged in as company admin of "11111111-1111-4111-8111-111111111111"
    And a target user exists with role admin
    When I delete the target user
    Then the response status should be 403

  # ── Deletion (Shop Manager) ─────────────────────────────────────────────────

  Scenario: Shop manager cannot delete anyone
    Given a company "11111111-1111-4111-8111-111111111111" exists
    And a shop "aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa" exists for company "11111111-1111-4111-8111-111111111111"
    And I am logged in as shop manager of "aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa"
    And a target user exists with no role
    When I delete the target user
    Then the response status should be 403

  # ── Deletion (No role / Unauthenticated) ────────────────────────────────────

  Scenario: User with no role cannot delete anyone
    Given I am logged in as a user with no role
    And a target user exists with no role
    When I delete the target user
    Then the response status should be 403

  Scenario: Deleting without authentication returns 401
    When I delete user "a1b2c3d4-e5f6-4a7b-8c9d-0e1f2a3b4c5d"
    Then the response status should be 401

  Scenario: Deleting a non-existent user returns 404
    Given I am logged in as admin
    When I delete user "a1b2c3d4-e5f6-4a7b-8c9d-0e1f2a3b4c5d"
    Then the response status should be 404

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

  Scenario: Listing users without a token returns 401
    When I list users
    Then the response status should be 401

  Scenario: Listing users without any role returns 403
    Given I am logged in as "alice@example.com" with password "secretpass"
    When I list users
    Then the response status should be 403

  Scenario: Admin sees all users
    Given a user is registered with email "alice@example.com" and password "secretpass"
    And a user is registered with email "bob@example.com" and password "secretpass"
    And I am logged in as admin
    When I list users
    Then the response status should be 200
    And the response should contain field "data"
    And the response should contain field "pagination"
    And the "data" array should have 3 items
    And the response "pagination.total" should equal "3"

  Scenario: Admin can filter listing by company
    Given a company "11111111-1111-4111-8111-111111111111" exists
    And a user is registered with email "cm@example.com" and password "secretpass" with role "company_admin" for company "11111111-1111-4111-8111-111111111111"
    And I am logged in as admin
    When I list users filtered by company "11111111-1111-4111-8111-111111111111"
    Then the response status should be 200
    And the "data" array should have 1 item

  Scenario: Admin respects pagination limit
    Given a user is registered with email "alice@example.com" and password "secretpass"
    And a user is registered with email "bob@example.com" and password "secretpass"
    And a user is registered with email "charlie@example.com" and password "secretpass"
    And I am logged in as admin
    When I list users on page 1 with limit 2
    Then the response status should be 200
    And the "data" array should have 2 items
    And the response "pagination.total" should equal "4"
    And the response "pagination.pages" should equal "2"

  Scenario: Admin second page returns remainder
    Given a user is registered with email "alice@example.com" and password "secretpass"
    And a user is registered with email "bob@example.com" and password "secretpass"
    And a user is registered with email "charlie@example.com" and password "secretpass"
    And I am logged in as admin
    When I list users on page 2 with limit 2
    Then the response status should be 200
    And the "data" array should have 2 items

  Scenario: Admin listing is ordered by email
    Given a user is registered with email "charlie@example.com" and password "secretpass"
    And a user is registered with email "alice@example.com" and password "secretpass"
    And I am logged in as admin
    When I list users
    Then the response status should be 200
    And the first "data" item "email" should equal "alice@example.com"

  Scenario: Company manager sees only users in their company
    Given a company "11111111-1111-4111-8111-111111111111" exists
    And a company "22222222-2222-4222-8222-222222222222" exists
    And a user is registered with email "cm2@example.com" and password "secretpass" with role "company_admin" for company "22222222-2222-4222-8222-222222222222"
    And I am logged in as company admin of "11111111-1111-4111-8111-111111111111"
    When I list users
    Then the response status should be 200
    And the "data" array should have 1 item

  Scenario: Company manager can filter by shop
    Given a company "11111111-1111-4111-8111-111111111111" exists
    And a shop "aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa" exists for company "11111111-1111-4111-8111-111111111111"
    And a shop "aaaa2222-aaaa-4aaa-8aaa-aaaaaaaaaaaa" exists for company "11111111-1111-4111-8111-111111111111"
    And a user is registered with email "sm1@example.com" and password "secretpass" with role "shop_manager" for shop "aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa"
    And a user is registered with email "sm2@example.com" and password "secretpass" with role "shop_manager" for shop "aaaa2222-aaaa-4aaa-8aaa-aaaaaaaaaaaa"
    And I am logged in as company admin of "11111111-1111-4111-8111-111111111111"
    When I list users filtered by shop "aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa"
    Then the response status should be 200
    And the "data" array should have 1 item

  Scenario: Shop manager sees only users in their shop
    Given a company "11111111-1111-4111-8111-111111111111" exists
    And a shop "aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa" exists for company "11111111-1111-4111-8111-111111111111"
    And a shop "aaaa2222-aaaa-4aaa-8aaa-aaaaaaaaaaaa" exists for company "11111111-1111-4111-8111-111111111111"
    And a user is registered with email "sm2@example.com" and password "secretpass" with role "shop_manager" for shop "aaaa2222-aaaa-4aaa-8aaa-aaaaaaaaaaaa"
    And I am logged in as shop manager of "aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa"
    When I list users
    Then the response status should be 200
    And the "data" array should have 1 item
