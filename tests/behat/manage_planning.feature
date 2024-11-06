@mod @mod_competvet
Feature: Manage Plannings
  In order to manage session plannings
  As a teacher
  I need to be able to create, update, and delete plannings
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

  @javascript
  Scenario: Add a new planning
    Given I am on the "S1" Activity page logged in as "teacher1"
    And I navigate to "Planning" in current page administration
    When I click the link with data-action "add"
    And I update date "startdate" to "2024-08-24T16:00" in row number "1"
    And I update date "enddate" to "2025-12-25T11:00" in row number "1"
    And I select "Group1" in the "groupid" field in row number "1"
    And I update "session" to "Session1" in row number "1"
    And I click the button with data-action "save" in row number "1"
    And I should see "Session1" in the planning table
    And I am on the "S1" Activity page logged in as "teacher1"
    Then I should see "Group1" in the ".competvet-grade-table" "css_element"

  @javascript
  Scenario: Update a planning
    Given I am on the "S1" Activity page logged in as "teacher1"
    And I navigate to "Planning" in current page administration
    And I click the link with data-action "add"
    And I update date "startdate" to "2024-08-24T16:00" in row number "1"
    And I update date "enddate" to "2025-12-25T11:00" in row number "1"
    And I select "Group1" in the "groupid" field in row number "1"
    And I update "session" to "Session1" in row number "1"
    And I click the button with data-action "save" in row number "1"
    And I should see "Session1" in the planning table
    And I click the button with data-action "edit" in row number "1"
    And I update date "startdate" to "2024-08-24T16:00" in row number "1"
    And I select "Group2" in the "groupid" field in row number "1"
    When I click the button with data-action "save" in row number "1"
    And I wait "5" seconds
    And I am on the "S1" Activity page logged in as "teacher1"
    Then I should see "Group2" in the ".competvet-grade-table" "css_element"

  @javascript
  Scenario: Delete a planning
    Given I am on the "S1" Activity page logged in as "teacher1"
    And I navigate to "Planning" in current page administration
    And I click the link with data-action "add"
    And I update date "startdate" to "2024-08-24T16:00" in row number "1"
    And I update date "enddate" to "2025-12-25T11:00" in row number "1"
    And I select "Group1" in the "groupid" field in row number "1"
    And I update "session" to "Session1" in row number "1"
    And I click the button with data-action "save" in row number "1"
    And I am on the "S1" Activity page logged in as "teacher1"
    And I should see "Group1" in the ".competvet-grade-table" "css_element"
    And I navigate to "Planning" in current page administration
    When I click the button with data-action "delete" in row number "1"
    And I am on the "S1" Activity page logged in as "teacher1"
    Then I should not see "Group1" in the ".competvet-grade-table" "css_element"
