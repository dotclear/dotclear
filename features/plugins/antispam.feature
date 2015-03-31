Feature: Manage comment
  In order to manage my blog
  As an administrator
  I need to be able to manage antispam

  Background: init
    Given a user:
      | username | password | status |
      | user1    | pass1    | admin  |
    Given a blog:
      | blog_id   | blog_name  | blog_url          |
      | other_blog| Other Blog | http://other.blog |

  Scenario: Add a new badword
    Given I am on "/admin/"
    And I am logged in as "user1" with password "pass1"
    When I go to "/admin/plugin.php?p=antispam&f=dcFilterWords"
    Then I should see "No word in list."
    When I fill in "Add a word" with "MyWord"
    And I press "Add"
    Then I should see "MyWord" in the ".antispam .local" element

  # bug #1647
  Scenario: Can add a badword to severals blogs
    Given I am on "/admin/"
    And I am logged in as "user1" with password "pass1"
    # add word to first blog
    When I switch to blog "Other Blog"
    And I go to "/admin/plugin.php?p=antispam&f=dcFilterWords"
    When I fill in "Add a word" with "MyWord"
    And I press "Add"
    Then I should see "MyWord" in the ".antispam" element
    # try to add to other blog
    When I switch to blog "My first blog"
    And I go to "/admin/plugin.php?p=antispam&f=dcFilterWords"
    When I fill in "Add a word" with "MyWord"
    And I press "Add"
    Then I should not see "This word exists"
    And I should see "MyWord" in the ".antispam .local" element

  Scenario: Can add a badword to all blogs even if its already local to one
    Given I am on "/admin/"
    And I am logged in as "user1" with password "pass1"
    # add word to first blog
    When I switch to blog "Other Blog"
    And I go to "/admin/plugin.php?p=antispam&f=dcFilterWords"
    When I fill in "Add a word" with "MyWord"
    And I press "Add"
    Then I should see "MyWord" in the ".antispam" element
    # try to add to other blog
    When I switch to blog "My first blog"
    And I go to "/admin/plugin.php?p=antispam&f=dcFilterWords"
    When I fill in "Add a word" with "MyWord"
    And I check "Global word (used for all blogs)"
    And I press "Add"
    Then I should not see "This word exists"
    And I should see "MyWord" in the ".antispam" element
