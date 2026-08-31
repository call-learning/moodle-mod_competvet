@mod @mod_competvet
Feature: Duplicating criteria grids in mod_competvet
  In order to base a new criteria grid on an existing one
  As a CompetVet teacher
  I need to be able to duplicate criteria grids

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
  Scenario: Administrator duplicates the default evaluation grid
    Given I am logged in as "admin"
    And I navigate to the manage criteria page
    And I should see "Savoir être"
    When I duplicate the grid named "Grille d'évaluation par défaut (EVAL)"
    Then I should see "Grille d'évaluation par défaut (EVAL) (copy)"
    And I should see the "edit" action button in the grid named "Grille d'évaluation par défaut (EVAL) (copy)"
    And I should not see the "edit" action button in the grid named "Grille d'évaluation par défaut (EVAL)"
    And I should see the "duplicate" action button in the grid named "Grille d'évaluation par défaut (EVAL)"
    And I change criterium row "1" in the grid named "Grille d'évaluation par défaut (EVAL) (copy)" to "Critère dupliqué"
    And I should see "Critère dupliqué"
    And I reload the page
    Then I should see "Critère dupliqué"
    And I should see "Savoir être"

  @javascript
  Scenario: Editing teacher duplicates the activity certification grid
    Given I am logged in as "teacher1"
    And I am on the "S1" Activity page logged in as "teacher1"
    And I follow "Criteria"
    And I should see "Savoir être"
    When I duplicate the grid named "Activity certification grid"
    Then I should see "Activity certification grid (copy)"
    And I should see the "edit" action button in the grid named "Activity certification grid (copy)"
    And I should see the "duplicate" action button in the grid named "Activity certification grid"
    And I should not see the "delete" action button in the grid named "Activity certification grid"
    And I should see the "delete" action button in the grid named "Activity certification grid (copy)"
    And I change criterium row "1" in the grid named "Activity certification grid (copy)" to "Critère dupliqué"
    And I should see "Critère dupliqué"
    And I reload the page
    Then I should see "Critère dupliqué"
    And I should see "Savoir être"
