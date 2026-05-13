Feature: Company API
  As an API consumer
  I want to manage companies

  # ── Create ────────────────────────────────────────────────────────────────────

  Scenario: Admin creates a company
    Given I am logged in as admin
    When I create a company with name "Acme Corp"
    Then the response status should be 201
    And the response should contain field "id"

  Scenario: Creating a company without authentication returns 401
    When I create a company with name "Acme Corp"
    Then the response status should be 401

  Scenario: Company manager cannot create a company
    Given a company "11111111-1111-4111-8111-111111111111" exists
    And I am logged in as company manager of "11111111-1111-4111-8111-111111111111"
    When I create a company with name "New Corp"
    Then the response status should be 403

  Scenario: Creating a company with an existing name returns 409
    Given I am logged in as admin
    And I create a company with name "Acme Corp"
    When I create a company with name "Acme Corp"
    Then the response status should be 409

  Scenario: Creating a company without a name returns 422
    Given I am logged in as admin
    When I create a company with name ""
    Then the response status should be 422

  # ── List ──────────────────────────────────────────────────────────────────────

  Scenario: Admin lists companies
    Given I am logged in as admin
    And I create a company with name "Alpha Corp"
    And I create a company with name "Beta Corp"
    When I list companies
    Then the response status should be 200
    And the response should contain field "data"
    And the response should contain field "pagination"
    And the "data" array should have 2 items

  Scenario: Listing companies without authentication returns 401
    When I list companies
    Then the response status should be 401

  Scenario: Company manager cannot list all companies
    Given a company "11111111-1111-4111-8111-111111111111" exists
    And I am logged in as company manager of "11111111-1111-4111-8111-111111111111"
    When I list companies
    Then the response status should be 403

  Scenario: Admin can paginate company listing
    Given I am logged in as admin
    And I create a company with name "Alpha Corp"
    And I create a company with name "Beta Corp"
    And I create a company with name "Gamma Corp"
    When I list companies on page 1 with limit 2
    Then the response status should be 200
    And the "data" array should have 2 items
    And the response "pagination.total" should equal "3"

  # ── Get ───────────────────────────────────────────────────────────────────────

  Scenario: Admin gets a company by id
    Given I am logged in as admin
    And I create a company with name "Acme Corp"
    When I get the last created company
    Then the response status should be 200
    And the response should contain field "id"
    And the response field "name" should equal "Acme Corp"

  Scenario: Getting a company without authentication returns 401
    Given a company "11111111-1111-4111-8111-111111111111" exists
    When I get the company "11111111-1111-4111-8111-111111111111"
    Then the response status should be 401

  Scenario: Company manager gets their own company
    Given a company "11111111-1111-4111-8111-111111111111" exists
    And I am logged in as company manager of "11111111-1111-4111-8111-111111111111"
    When I get the company "11111111-1111-4111-8111-111111111111"
    Then the response status should be 200

  Scenario: Company manager cannot get another company
    Given a company "11111111-1111-4111-8111-111111111111" exists
    And a company "22222222-2222-4222-8222-222222222222" exists
    And I am logged in as company manager of "11111111-1111-4111-8111-111111111111"
    When I get the company "22222222-2222-4222-8222-222222222222"
    Then the response status should be 403

  Scenario: Getting a non-existent company returns 404
    Given I am logged in as admin
    When I get the company "99999999-9999-4999-8999-999999999999"
    Then the response status should be 404

  Scenario: Getting a company with a malformed id returns 400
    Given I am logged in as admin
    When I get the company "not-a-uuid"
    Then the response status should be 400

  # ── Update ────────────────────────────────────────────────────────────────────

  Scenario: Admin updates a company name
    Given I am logged in as admin
    And I create a company with name "Old Name"
    When I update the last created company with name "New Name"
    Then the response status should be 200

  Scenario: Updating a company without authentication returns 401
    Given a company "11111111-1111-4111-8111-111111111111" exists
    When I update the company "11111111-1111-4111-8111-111111111111" with name "New Name"
    Then the response status should be 401

  Scenario: Company manager updates their own company
    Given a company "11111111-1111-4111-8111-111111111111" exists
    And I am logged in as company manager of "11111111-1111-4111-8111-111111111111"
    When I update the company "11111111-1111-4111-8111-111111111111" with name "Updated Name"
    Then the response status should be 200

  Scenario: Company manager cannot update another company
    Given a company "11111111-1111-4111-8111-111111111111" exists
    And a company "22222222-2222-4222-8222-222222222222" exists
    And I am logged in as company manager of "11111111-1111-4111-8111-111111111111"
    When I update the company "22222222-2222-4222-8222-222222222222" with name "Hacked"
    Then the response status should be 403

  Scenario: Updating a company with an existing name returns 409
    Given I am logged in as admin
    And I create a company with name "Alpha Corp"
    And I create a company with name "Beta Corp"
    When I update the last created company with name "Alpha Corp"
    Then the response status should be 409

  Scenario: Updating a non-existent company returns 404
    Given I am logged in as admin
    When I update the company "99999999-9999-4999-8999-999999999999" with name "Ghost"
    Then the response status should be 404

  # ── Delete ────────────────────────────────────────────────────────────────────

  Scenario: Admin deletes a company
    Given I am logged in as admin
    And I create a company with name "To Be Deleted"
    When I delete the last created company
    Then the response status should be 200

  Scenario: Deleting a company without authentication returns 401
    Given a company "11111111-1111-4111-8111-111111111111" exists
    When I delete the company "11111111-1111-4111-8111-111111111111"
    Then the response status should be 401

  Scenario: Company manager cannot delete a company
    Given a company "11111111-1111-4111-8111-111111111111" exists
    And I am logged in as company manager of "11111111-1111-4111-8111-111111111111"
    When I delete the company "11111111-1111-4111-8111-111111111111"
    Then the response status should be 403

  Scenario: Deleting a non-existent company returns 404
    Given I am logged in as admin
    When I delete the company "99999999-9999-4999-8999-999999999999"
    Then the response status should be 404
