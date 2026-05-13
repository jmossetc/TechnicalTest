Feature: Shop API
  As an API consumer
  I want to manage shops within companies

  # ── Create ────────────────────────────────────────────────────────────────────

  Scenario: Admin creates a shop in a company
    Given I am logged in as admin
    And a company "11111111-1111-4111-8111-111111111111" exists
    When I create a shop with name "Main Street" for company "11111111-1111-4111-8111-111111111111"
    Then the response status should be 201
    And the response should contain field "id"

  Scenario: Creating a shop without authentication returns 401
    Given a company "11111111-1111-4111-8111-111111111111" exists
    When I create a shop with name "Main Street" for company "11111111-1111-4111-8111-111111111111"
    Then the response status should be 401

  Scenario: Company manager creates a shop in their company
    Given a company "11111111-1111-4111-8111-111111111111" exists
    And I am logged in as company manager of "11111111-1111-4111-8111-111111111111"
    When I create a shop with name "West End" for company "11111111-1111-4111-8111-111111111111"
    Then the response status should be 201

  Scenario: Company manager cannot create a shop in another company
    Given a company "11111111-1111-4111-8111-111111111111" exists
    And a company "22222222-2222-4222-8222-222222222222" exists
    And I am logged in as company manager of "11111111-1111-4111-8111-111111111111"
    When I create a shop with name "Intruder" for company "22222222-2222-4222-8222-222222222222"
    Then the response status should be 403

  Scenario: Shop manager cannot create a shop
    Given a company "11111111-1111-4111-8111-111111111111" exists
    And a shop "aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa" exists for company "11111111-1111-4111-8111-111111111111"
    And I am logged in as shop manager of "aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa"
    When I create a shop with name "New Branch" for company "11111111-1111-4111-8111-111111111111"
    Then the response status should be 403

  Scenario: Creating a shop with a duplicate name in the same company returns 409
    Given I am logged in as admin
    And a company "11111111-1111-4111-8111-111111111111" exists
    And I create a shop with name "Flagship" for company "11111111-1111-4111-8111-111111111111"
    When I create a shop with name "Flagship" for company "11111111-1111-4111-8111-111111111111"
    Then the response status should be 409

  Scenario: Creating a shop without a name returns 422
    Given I am logged in as admin
    And a company "11111111-1111-4111-8111-111111111111" exists
    When I create a shop with name "" for company "11111111-1111-4111-8111-111111111111"
    Then the response status should be 422

  # ── List ──────────────────────────────────────────────────────────────────────

  Scenario: Admin lists shops for a company
    Given I am logged in as admin
    And a company "11111111-1111-4111-8111-111111111111" exists
    And I create a shop with name "Alpha" for company "11111111-1111-4111-8111-111111111111"
    And I create a shop with name "Beta" for company "11111111-1111-4111-8111-111111111111"
    When I list shops for company "11111111-1111-4111-8111-111111111111"
    Then the response status should be 200
    And the response should contain field "data"
    And the response should contain field "pagination"
    And the "data" array should have 2 items

  Scenario: Listing shops without authentication returns 401
    Given a company "11111111-1111-4111-8111-111111111111" exists
    When I list shops for company "11111111-1111-4111-8111-111111111111"
    Then the response status should be 401

  Scenario: Company manager lists shops in their company
    Given a company "11111111-1111-4111-8111-111111111111" exists
    And I am logged in as company manager of "11111111-1111-4111-8111-111111111111"
    And I create a shop with name "Shop One" for company "11111111-1111-4111-8111-111111111111"
    When I list shops for company "11111111-1111-4111-8111-111111111111"
    Then the response status should be 200
    And the "data" array should have 1 item

  Scenario: Company manager cannot list shops of another company
    Given a company "11111111-1111-4111-8111-111111111111" exists
    And a company "22222222-2222-4222-8222-222222222222" exists
    And I am logged in as company manager of "11111111-1111-4111-8111-111111111111"
    When I list shops for company "22222222-2222-4222-8222-222222222222"
    Then the response status should be 403

  Scenario: Shop manager cannot list shops
    Given a company "11111111-1111-4111-8111-111111111111" exists
    And a shop "aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa" exists for company "11111111-1111-4111-8111-111111111111"
    And I am logged in as shop manager of "aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa"
    When I list shops for company "11111111-1111-4111-8111-111111111111"
    Then the response status should be 403

  Scenario: Admin can paginate shop listing
    Given I am logged in as admin
    And a company "11111111-1111-4111-8111-111111111111" exists
    And I create a shop with name "Alpha" for company "11111111-1111-4111-8111-111111111111"
    And I create a shop with name "Beta" for company "11111111-1111-4111-8111-111111111111"
    And I create a shop with name "Gamma" for company "11111111-1111-4111-8111-111111111111"
    When I list shops for company "11111111-1111-4111-8111-111111111111" on page 1 with limit 2
    Then the response status should be 200
    And the "data" array should have 2 items
    And the response "pagination.total" should equal "3"

  # ── Get ───────────────────────────────────────────────────────────────────────

  Scenario: Admin gets a shop by id
    Given I am logged in as admin
    And a company "11111111-1111-4111-8111-111111111111" exists
    And I create a shop with name "Flagship" for company "11111111-1111-4111-8111-111111111111"
    When I get the last created shop
    Then the response status should be 200
    And the response should contain field "id"
    And the response field "name" should equal "Flagship"

  Scenario: Getting a shop without authentication returns 401
    Given a company "11111111-1111-4111-8111-111111111111" exists
    And a shop "aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa" exists for company "11111111-1111-4111-8111-111111111111"
    When I get the shop "aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa"
    Then the response status should be 401

  Scenario: Company manager gets a shop in their company
    Given a company "11111111-1111-4111-8111-111111111111" exists
    And a shop "aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa" exists for company "11111111-1111-4111-8111-111111111111"
    And I am logged in as company manager of "11111111-1111-4111-8111-111111111111"
    When I get the shop "aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa"
    Then the response status should be 200

  Scenario: Shop manager gets their own shop
    Given a company "11111111-1111-4111-8111-111111111111" exists
    And a shop "aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa" exists for company "11111111-1111-4111-8111-111111111111"
    And I am logged in as shop manager of "aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa"
    When I get the shop "aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa"
    Then the response status should be 200

  Scenario: Company manager cannot get a shop of another company
    Given a company "11111111-1111-4111-8111-111111111111" exists
    And a company "22222222-2222-4222-8222-222222222222" exists
    And a shop "bbbb1111-bbbb-4bbb-8bbb-bbbbbbbbbbbb" exists for company "22222222-2222-4222-8222-222222222222"
    And I am logged in as company manager of "11111111-1111-4111-8111-111111111111"
    When I get the shop "bbbb1111-bbbb-4bbb-8bbb-bbbbbbbbbbbb"
    Then the response status should be 403

  Scenario: Shop manager cannot get another shop
    Given a company "11111111-1111-4111-8111-111111111111" exists
    And a shop "aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa" exists for company "11111111-1111-4111-8111-111111111111"
    And a shop "aaaa2222-aaaa-4aaa-8aaa-aaaaaaaaaaaa" exists for company "11111111-1111-4111-8111-111111111111"
    And I am logged in as shop manager of "aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa"
    When I get the shop "aaaa2222-aaaa-4aaa-8aaa-aaaaaaaaaaaa"
    Then the response status should be 403

  Scenario: Getting a non-existent shop returns 404
    Given I am logged in as admin
    When I get the shop "99999999-9999-4999-8999-999999999999"
    Then the response status should be 404

  Scenario: Getting a shop with a malformed id returns 400
    Given I am logged in as admin
    When I get the shop "not-a-uuid"
    Then the response status should be 400

  # ── Update ────────────────────────────────────────────────────────────────────

  Scenario: Admin updates a shop name
    Given I am logged in as admin
    And a company "11111111-1111-4111-8111-111111111111" exists
    And I create a shop with name "Old Name" for company "11111111-1111-4111-8111-111111111111"
    When I update the last created shop with name "New Name"
    Then the response status should be 200

  Scenario: Updating a shop without authentication returns 401
    Given a company "11111111-1111-4111-8111-111111111111" exists
    And a shop "aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa" exists for company "11111111-1111-4111-8111-111111111111"
    When I update the shop "aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa" with name "New Name"
    Then the response status should be 401

  Scenario: Company manager updates a shop in their company
    Given a company "11111111-1111-4111-8111-111111111111" exists
    And a shop "aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa" exists for company "11111111-1111-4111-8111-111111111111"
    And I am logged in as company manager of "11111111-1111-4111-8111-111111111111"
    When I update the shop "aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa" with name "Updated"
    Then the response status should be 200

  Scenario: Shop manager updates their own shop
    Given a company "11111111-1111-4111-8111-111111111111" exists
    And a shop "aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa" exists for company "11111111-1111-4111-8111-111111111111"
    And I am logged in as shop manager of "aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa"
    When I update the shop "aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa" with name "My Updated Shop"
    Then the response status should be 200

  Scenario: Company manager cannot update a shop in another company
    Given a company "11111111-1111-4111-8111-111111111111" exists
    And a company "22222222-2222-4222-8222-222222222222" exists
    And a shop "bbbb1111-bbbb-4bbb-8bbb-bbbbbbbbbbbb" exists for company "22222222-2222-4222-8222-222222222222"
    And I am logged in as company manager of "11111111-1111-4111-8111-111111111111"
    When I update the shop "bbbb1111-bbbb-4bbb-8bbb-bbbbbbbbbbbb" with name "Hacked"
    Then the response status should be 403

  Scenario: Shop manager cannot update another shop
    Given a company "11111111-1111-4111-8111-111111111111" exists
    And a shop "aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa" exists for company "11111111-1111-4111-8111-111111111111"
    And a shop "aaaa2222-aaaa-4aaa-8aaa-aaaaaaaaaaaa" exists for company "11111111-1111-4111-8111-111111111111"
    And I am logged in as shop manager of "aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa"
    When I update the shop "aaaa2222-aaaa-4aaa-8aaa-aaaaaaaaaaaa" with name "Stolen"
    Then the response status should be 403

  Scenario: Updating a shop with a duplicate name in the same company returns 409
    Given I am logged in as admin
    And a company "11111111-1111-4111-8111-111111111111" exists
    And I create a shop with name "Alpha" for company "11111111-1111-4111-8111-111111111111"
    And I create a shop with name "Beta" for company "11111111-1111-4111-8111-111111111111"
    When I update the last created shop with name "Alpha"
    Then the response status should be 409

  Scenario: Updating a non-existent shop returns 404
    Given I am logged in as admin
    When I update the shop "99999999-9999-4999-8999-999999999999" with name "Ghost"
    Then the response status should be 404

  # ── Delete ────────────────────────────────────────────────────────────────────

  Scenario: Admin deletes a shop
    Given I am logged in as admin
    And a company "11111111-1111-4111-8111-111111111111" exists
    And I create a shop with name "To Delete" for company "11111111-1111-4111-8111-111111111111"
    When I delete the last created shop
    Then the response status should be 200

  Scenario: Deleting a shop without authentication returns 401
    Given a company "11111111-1111-4111-8111-111111111111" exists
    And a shop "aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa" exists for company "11111111-1111-4111-8111-111111111111"
    When I delete the shop "aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa"
    Then the response status should be 401

  Scenario: Company manager deletes a shop in their company
    Given a company "11111111-1111-4111-8111-111111111111" exists
    And a shop "aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa" exists for company "11111111-1111-4111-8111-111111111111"
    And I am logged in as company manager of "11111111-1111-4111-8111-111111111111"
    When I delete the shop "aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa"
    Then the response status should be 200

  Scenario: Company manager cannot delete a shop of another company
    Given a company "11111111-1111-4111-8111-111111111111" exists
    And a company "22222222-2222-4222-8222-222222222222" exists
    And a shop "bbbb1111-bbbb-4bbb-8bbb-bbbbbbbbbbbb" exists for company "22222222-2222-4222-8222-222222222222"
    And I am logged in as company manager of "11111111-1111-4111-8111-111111111111"
    When I delete the shop "bbbb1111-bbbb-4bbb-8bbb-bbbbbbbbbbbb"
    Then the response status should be 403

  Scenario: Shop manager cannot delete any shop
    Given a company "11111111-1111-4111-8111-111111111111" exists
    And a shop "aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa" exists for company "11111111-1111-4111-8111-111111111111"
    And I am logged in as shop manager of "aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa"
    When I delete the shop "aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa"
    Then the response status should be 403

  Scenario: Deleting a non-existent shop returns 404
    Given I am logged in as admin
    When I delete the shop "99999999-9999-4999-8999-999999999999"
    Then the response status should be 404
