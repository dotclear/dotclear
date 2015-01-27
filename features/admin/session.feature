Feature: Manage content
  In order to manage my blog
  As an administrator
  I need to be able to connect to administration area

  Background: init
    Given a user:
      | username | password | status |
      | user1    | pass1    | normal |

  Scenario: Login
    Given I am on "/admin/"
    And I am logged in as "user1" with password "pass1"
    Then I should see "Dashboard"
    And I should see "My preferences"

  Scenario: Logout
    Given I am on "/admin/"
    And I am logged in as "user1" with password "pass1"
    When I follow "My preferences"
    When I follow "Logout"
    Then I should not see "My preferences"

  Scenario: Remember me
    Given I am on "/admin/"
    And I am logged in as "user1" with password "pass1" with remember me
    Then I should see "My preferences"
    When I restart my browser
    Then I should see "My preferences"
