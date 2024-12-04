@mod @mod_competvet @javascript
Feature: Testing manage_criteria in mod_competvet
  In order to manage criteria
  as an admin
  I need to be able change the criteria text

  Scenario: Edit the Evaluation criteria and option texts
    Given I am logged in as "admin"
    And I navigate to the manage criteria page
    Then I should see "Savoir être"
    And I should see "Respect des horaires de travail"
    And I change criterium row "1" in grid row "1" to "Aisance relationnelle"
    And I should see "Aisance relationnelle"
    And I change option row "1" in criterium row "1" in grid row "1" to "Rigueur horaire"
    And I reload the page
    And I should see "Rigueur horaire"

  Scenario: Edit the Certification criteria
    Given I am logged in as "admin"
    And I navigate to the manage criteria page
    And I press "Certification criteria"
    And I should see "(CERTIF)"
    And I change criterium row "1" in grid row "1" to "Effectuer une évaluation clinique"
    And I change criterium row "2" in grid row "1" to "Réaliser un diagnostic"
    And I reload the page
    And I press "Certification criteria"
    Then I should see "Effectuer une évaluation clinique"
    And I should see "Réaliser un diagnostic"

  Scenario: Edit the List criteria
    Given I am logged in as "admin"
    And I navigate to the manage criteria page
    And I press "List criteria"
    And I should see "List criteria"
    And I change criterium row "1" in grid row "1" to "Quantité et variété des situations cliniques"
    And I change option row "1" in criterium row "1" in grid row "1" to "Quantité"
    And I reload the page
    And I press "List criteria"
    Then I should see "Quantité et variété des situations cliniques"
    And I should see "Quantité"