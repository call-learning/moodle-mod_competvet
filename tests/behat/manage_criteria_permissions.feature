@mod @mod_competvet
Feature: Manage activity criteria permissions in mod_competvet
  In order to maintain activity-specific criteria
  As a CompetVet teacher
  I need to edit criteria in a CompetVet activity

  Background:
    Given the CompetVet default grids have been initialised
    And the following "courses" exist:
      | fullname            | shortname | enablecompletion |
      | Compet Vet Course 1 | CVET1     | 1                |
    And the following "users" exist:
      | username  | firstname | lastname | email                |
      | teacher1  | Teacher   | One      | teacher1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | CVET1  | editingteacher |
    And the following "activities" exist:
      | activity  | course | idnumber | intro | name    | shortname | completion | completionview | situationtags | grade | haseval | hascertif | hascase |
      | competvet | CVET1  | S1       | x     | MEDCHIR | SIT1      | 0          | 1              | y:1           | 100   | 1       | 1         | 0       |
    And the CompetVet activity-specific certification grid exists for "SIT1"

  @javascript
  Scenario: Editing teacher can edit activity criteria
    Given I am on the "S1" Activity page logged in as "teacher1"
    When I follow "Criteria"
    Then I should see "Savoir être"
    And I change criterium row "1" in grid row "1" to "Critère de l'activité"
    And I reload the page
    And I should see "Critère de l'activité"

  @javascript
  Scenario: Restricted global grid administrator opens global criteria from the activity administration menu
    Given the following "roles" exist:
      | name                | shortname          |
      | Global grid manager | globalgridmanager |
    And the following "role capability" exists:
      | role                               | globalgridmanager |
      | mod/competvet:manageglobalcriteria | allow             |
    And the following "system role assigns" exist:
      | user     | course               | role              |
      | teacher1 | Acceptance test site | globalgridmanager |
    And I am on the "S1" Activity page logged in as "teacher1"
    When I navigate to "Manage global CompetVet criteria" in current page administration
    Then I should see "Savoir être"
