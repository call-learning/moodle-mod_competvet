@mod @mod_competvet
Feature: Check disabled module features
  In order to verify the visibility of tabs
  As a teacher
  I need to confirm that only the appropriate tabs are shown when specific features are disabled

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
      | activity  | course | idnumber | intro | name    | shortname | completion | completionview | situationtags | grade | haseval | hascertif | hascase |
      | competvet | CVET1  | S1       | x     | MEDCHIR | SIT1      | 0          | 1              | y:1           | 100   | 1       | 1         | 0       |
    And the following "mod_competvet > plannings" exist:
      | situation | group | startdate        | enddate               | session  |
      | SIT1      | G1    | last Monday      | Monday next week      | SESSION1 |
      | SIT1      | G2    | Monday next week | Monday next fortnight | SESSION1 |

  @javascript
  Scenario: Verify only the appropriate tabs are shown when list feature is disabled
    Given I am on the "S1" Activity page logged in as "teacher1"
    And I open grading page for "Student One"
    Then I should see "evaluate" tab
    And I should see "certify" tab
    And I should not see "list" tab

  @javascript
  Scenario: Verify only the appropriate tabs are shown when eval feature is disabled
    Given I am on the "S1" Activity page logged in as "teacher1"
    And I navigate to "Settings" in current page administration
    And I expand all fieldsets
    And I set the following fields to these values:
      | haseval   | 0 |
      | hascertif | 1 |
      | hascase   | 1 |
    And I press "Save and display"
    And I open grading page for "Student One"
    Then I should not see "evaluate" tab
    And I should see "certify" tab
    And I should see "list" tab

  @javascript
  Scenario: Verify only the appropriate tabs are shown when certif feature is disabled
    Given I am on the "S1" Activity page logged in as "teacher1"
    And I navigate to "Settings" in current page administration
    And I expand all fieldsets
    And I set the following fields to these values:
      | haseval   | 1 |
      | hascertif | 0 |
      | hascase   | 1 |
    And I press "Save and display"
    And I open grading page for "Student One"
    Then I should see "evaluate" tab
    And I should not see "certify" tab
    And I should see "list" tab

  @javascript
  Scenario: Verify only the appropriate tabs are shown when certif and eval features are disabled
    Given I am on the "S1" Activity page logged in as "teacher1"
    And I navigate to "Settings" in current page administration
    And I expand all fieldsets
    And I set the following fields to these values:
      | haseval   | 0 |
      | hascertif | 0 |
      | hascase   | 1 |
    And I press "Save and display"
    And I open grading page for "Student One"
    Then I should not see "evaluate" tab
    And I should not see "certify" tab
    And I should see "list" tab

  @javascript
  Scenario: Verify only the appropriate tabs are shown when case and certif features are disabled
    Given I am on the "S1" Activity page logged in as "teacher1"
    And I navigate to "Settings" in current page administration
    And I expand all fieldsets
    And I set the following fields to these values:
      | haseval   | 1 |
      | hascertif | 0 |
      | hascase   | 0 |
    And I press "Save and display"
    And I open grading page for "Student One"
    Then I should see "evaluate" tab
    And I should not see "certify" tab
    And I should not see "list" tab
