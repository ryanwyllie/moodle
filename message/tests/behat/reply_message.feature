@core @message @javascript
Feature: Reply message
  In order to communicate with fellow users
  As a user
  I need to be able to reply to a message

  Scenario: Reply to a message
    Given the following "users" exist:
      | username | firstname | lastname | email            |
      | user1    | User      | 1        | user1@example.com    |
      | user2    | User      | 2        | user2@example.com    |
    And I log in as "user2"
    And I send "User 2 to User 1" message to "User 1" user
    And I log out
    When I log in as "user1"
    And I follow "Messages" in the user menu
    And I select messages from "User 2" user in messages
    And I send "Reply to User 2" message in messages
    And I log out
    Then I log in as "user2"
    And I follow "Messages" in the user menu
    And I should see "Reply to User 2" message in messages
