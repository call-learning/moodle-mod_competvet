@mod @mod_competvet
Feature: Caselog page
  In order to transmit a clinical case clearly
  As a student
  I need to use the dedicated Caselog page

  Background:
    Given the following "courses" exist:
      | fullname            | shortname | enablecompletion |
      | Compet Vet Course 1 | CVET1     | 1                |
    And the following "users" exist:
      | username  | firstname | lastname | email                 |
      | student1  | Student   | One      | student1@example.com  |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | CVET1  | student |
    And the following "groups" exist:
      | course | name   | idnumber |
      | CVET1  | Group1 | G1       |
    And the following "group members" exist:
      | group | user     |
      | G1    | student1 |
    And the following "activities" exist:
      | activity  | course | idnumber | intro | name    | shortname | completion | completionview | situationtags | grade | hascase |
      | competvet | CVET1  | S1       | x     | MEDCHIR | SIT1      | 0          | 1               | y:1           | 100   | 1       |
    And the following "mod_competvet > plannings" exist:
      | situation | group | startdate   | enddate          | session  |
      | SIT1      | G1    | last Monday | Monday next week | SESSION1 |

  @javascript
  Scenario: Student opens the single-page Caselog form
    Given I am on the "S1" Activity page logged in as "student1"
    When I press "Add a clinical case"
    Then I should see "Add a clinical case transmission"
    And I should see "Transmission clinique (1200 caractères maximum)"
    And I should see "Réflexions et enseignements issus du cas (800 caractères maximum)"
    And I should see "Save draft"
    And I should see "Cancel"
    And I should see "Validate"
