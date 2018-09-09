@block @block_myoverview @javascript
Feature: The my overview block allows users to easily access their courses
  In order to enable the my overview block in a course
  As a student
  I can add the my overview block to my dashboard

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                | idnumber |
      | studentx | Student   | X        | studentx@example.com | S1       |
    And the following "courses" exist:
      | fullname | shortname | category | startdate                   | enddate         |
      | Course 1 | C1        | 0        | ##1 month ago##             | ##15 days ago## |
      | Course 2 | C2        | 0        | ##yesterday##               | ##tomorrow## |
      | Course 3 | C3        | 0        | ##yesterday##               | ##tomorrow## |
      | Course 4 | C4        | 0        | ##yesterday##               | ##tomorrow## |
      | Course 5 | C5        | 0        | ##first day of next month## | ##last day of next month## |
    And the following "course enrolments" exist:
      | user | course | role |
      | studentx | C1 | student |
      | studentx | C2 | student |
      | studentx | C3 | student |
      | studentx | C4 | student |
      | studentx | C5 | student |

  Scenario: View past courses
    Given I log in as "studentx"
    And I click on "All" "button" in the "Course overview" "block"
    When I click on "Past" "link" in the "Course overview" "block"
    Then I should see "Course 1" in the "Course overview" "block"
    And I should not see "Course 2" in the "Course overview" "block"
    And I should not see "Course 3" in the "Course overview" "block"
    And I should not see "Course 4" in the "Course overview" "block"
    And I should not see "Course 5" in the "Course overview" "block"
    And I log out