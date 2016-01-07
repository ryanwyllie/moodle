@mod @mod_forum
Feature: A teacher can pin discussions in forums
  In order to pin a discussion
  As a teacher
  I need to use the pin discussion selector

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                 |
      | teacher1 | Teacher   | 1        | teacher1@example.com  |
      | student1 | Student   | 1        | student1@example.com  |
    And the following "courses" exist:
      | fullname | shortname  | category  |
      | Course 1 | C1         | 0         |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
    And the following "activities" exist:
      | activity   | name                   | intro             | course | idnumber     | groupmode |
      | forum      | Test forum 1           | Test forum 1      | C1     | forum        | 0         |

  Scenario: A teacher can pin a discussion
    Given I log in as "student1"
    And I follow "Course 1"
    And I follow "Test forum 1"
    And I add a new discussion to "Test forum 1" forum with:
      | Subject | Discussion 1 |
      | Message | Test post message 1 |
    And I wait "1" seconds
    And I add a new discussion to "Test forum 1" forum with:
      | Subject | Discussion 2 |
      | Message | Test post message 2 |
    And I wait "1" seconds
    And I log out
    And I log in as "teacher1"
    And I follow "Course 1"
    And I follow "Test forum 1"
    And I follow "Discussion 1"
    When I press "Pin"
    Then "[aria-label='Previous discussion: Discussion 2']" "css_element" should exist

  Scenario: Multiple pinned discussions appear in order
    Given I log in as "teacher1"
    And I follow "Course 1"
    And I follow "Test forum 1"
    And I add a new discussion to "Test forum 1" forum with:
      | Subject | Discussion 1 |
      | Message | Test post message 1 |
    And I wait "1" seconds
    And I add a new discussion to "Test forum 1" forum with:
      | Subject | Discussion 2 |
      | Message | Test post message 2 |
      | Pinned  | true |
    And I wait "1" seconds
    And I add a new discussion to "Test forum 1" forum with:
      | Subject | Discussion 3 |
      | Message | Test post message 3 |
      | Pinned  | true |
    And I wait "1" seconds
    And I add a new discussion to "Test forum 1" forum with:
      | Subject | Discussion 4 |
      | Message | Test post message 4 |
      | Pinned  | true |
    And I wait "1" seconds
    And I add a new discussion to "Test forum 1" forum with:
      | Subject | Discussion 5 |
      | Message | Test post message 5 |
    And I wait "1" seconds
    And I follow "Course 1"
    And I follow "Test forum 1"
    When I follow "Discussion 3"
    Then "[aria-label='Previous discussion: Discussion 2']" "css_element" should exist
    And "[aria-label='Next discussion: Discussion 4']" "css_element" should exist
    And I follow "Discussion 2"
    And "[aria-label='Previous discussion: Discussion 5']" "css_element" should exist
    And "[aria-label='Next discussion: Discussion 3']" "css_element" should exist
    And I press "Unpin"
    And "[aria-label='Previous discussion: Discussion 1']" "css_element" should exist
    And "[aria-label='Next discussion: Discussion 5']" "css_element" should exist
