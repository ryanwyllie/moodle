@core @message @javascript
Feature: Search messages
  In order to communicate with fellow users
  As a user
  I need to be able to search my messages

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email            |
      | user1    | User      | 1        | user1@example.com    |
      | user2    | User      | 2        | user2@example.com    |
      | user3    | User      | 3        | user3@example.com    |
    And I log in as "user2"
    And I send "User 2 to User 1" message to "User 1" user
    And I log out
    And I log in as "user3"
    And I send "User 3 to User 1" message to "User 1" user
    And I log out

  Scenario: Search for messages
    When I log in as "user1"
    And I follow "Messages" in the user menu
    And I search for "User 2 to User 1" message in messages
    Then I should see "User 2" user in search results within messages
    And I should not see "User 3" user in search results within messages

  Scenario: Search for messages no results
    When I log in as "user1"
    And I follow "Messages" in the user menu
    And I search for "No messages" message in messages
    Then I should not see any search results within messages
