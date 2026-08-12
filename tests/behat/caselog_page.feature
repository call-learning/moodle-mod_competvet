@mod @mod_competvet
Feature: Caselog modal form
  In order to transmit a clinical case clearly
  As a student
  I need to use the Caselog modal form

  Background:
    Given the following "courses" exist:
      | fullname            | shortname | enablecompletion |
      | Compet Vet Course 1 | CVET1     | 1                |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | One      | student1@example.com |
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
      | competvet | CVET1  | S1       | x     | MEDCHIR | SIT1      | 0          | 1              | y:1           | 100   | 1       |
    And the following "mod_competvet > plannings" exist:
      | situation | group | startdate   | enddate          | session  |
      | SIT1      | G1    | last Monday | Monday next week | SESSION1 |
    And the CompetVet caselog schema has been initialised

  @javascript
  Scenario: Student opens the Caselog modal form
    Given I am on the "S1" Activity page logged in as "student1"
    And I navigate to the emulator page on the "SIT1" situation "last Monday > Monday next week > SESSION1 > SIT1" planning "list" page
    When I press "Add a clinical case"
    And I should see "Transmission clinique"
    And I should see "Réflexions et enseignements issus du cas"
    And I set the following fields to these values:
      | Nom de l'animal       | Rex Chien    |
      | Espèce                | Chien        |
      | Transmission clinique | Test message |
    And I press "Save"
    And I should see "Rex Chien"
