@mod @mod_competvet
Feature: Grade a student
  In order to edit completion settings without accidentally breaking user data
  As a teacher
  I need to edit the activity and use the unlock button if required

  Background:
    Given the following "courses" exist:
      | fullname            | shortname | enablecompletion |
      | Compet Vet Course 1 | CVET1     | 1                |
    And the following "users" exist:
      | username  | firstname | lastname | email                 |
      | teacher1  | Teacher   | One      | teacher1@example.com  |
      | observer1 | Observer  | One      | observer1@example.com |
      | observer2 | Observer  | Two      | observer2@example.com |
      | student1  | Student   | One      | student1@example.com  |
      | student2  | Student   | Two      | student2@example.com  |
    And the following "course enrolments" exist:
      | user      | course | role           |
      | teacher1  | CVET1  | editingteacher |
      | observer1 | CVET1  | observer       |
      | observer2 | CVET1  | observer       |
      | student1  | CVET1  | student        |
      | student2  | CVET1  | student        |
    And the following "groups" exist:
      | course | name   | idnumber |
      | CVET1  | Group1 | G1       |
      | CVET1  | Group2 | G2       |
    And the following "group members" exist:
      | group | user     |
      | G1    | student1 |
      | G2    | student2 |
    And the following "activities" exist:
      | activity  | course | idnumber | intro | name    | shortname | completion | completionview | situationtags | grade |
      | competvet | CVET1  | S1       | x     | MEDCHIR | SIT1      | 2          | 1              | y:1           | 100   |
    And the following "mod_competvet > plannings" exist:
      | situation | group | startdate        | enddate               | session  |
      | SIT1      | G1    | last Monday      | Monday next week      | SESSION1 |
      | SIT1      | G2    | last Sunday      | Monday next fortnight | SESSION2 |

  @javascript
  Scenario: Teacher can grade student
    Given I am on the "S1" Activity page logged in as "teacher1"
    And I open grading page for "Student One"
    And I set "75" in the "finalgrade" field within the form with data-region "globalgrade"
    And I set "Well done" in the "comment" field within the form with data-region "globalgrade"
    And I submit the form with data-region "globalgrade"
    And I click the link with text "Close evaluation"

    And I am on the "S1" Activity page logged in as "teacher1"
    And I open grading page for "Student Two"
    And I set "30" in the "finalgrade" field within the form with data-region "globalgrade"
    And I set "Better luck next time" in the "comment" field within the form with data-region "globalgrade"
    And I submit the form with data-region "globalgrade"
    And I click the link with text "Close evaluation"

    And I am on the "CVET1" "grades > Grader report > View" page
    And I should see "MEDCHIR" in the "user-grades" "table"
    Then the following should exist in the "user-grades" table:
      | -1-                | -2-                  | -3-       |
      | Student One        | student1@example.com | 75        |
      | Student Two        | student2@example.com | 30        |
