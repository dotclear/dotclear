Feature: See content
  In order to see posts
  As a site visitor
  I need to be able to see the website

  Scenario: I'm on a dotclear blog
    Given I am on homepage
    Then I should see "Powered by dotclear"
