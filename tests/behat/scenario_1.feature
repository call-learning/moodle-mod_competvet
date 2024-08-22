@local_competvet
Feature: Edit completion settings of an activity
  In order to edit completion settings without accidentally breaking user data
  As a teacher
  I need to edit the activity and use the unlock button if required

  Scenario: Edit completion settings of an activity
    Given the following "courses" exist:
      | fullname            | shortname | enablecompletion |
      | Compet Vet Course 1 | CVET1     | 1                |
    And the following "users" exist:
      | username  | firstname | lastname | email                 | password |
      | teacher1  | Teacher   | One      | teacher1@example.com  | password |
      | observer1 | Observer  | One      | observer1@example.com | password |
      | observer2 | Observer  | Two      | observer2@example.com | password |
      | student1  | Student   | One      | student1@example.com  | password |
      | student2  | Student   | Two      | student2@example.com  | password |
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
      | activity  | course | idnumber | intro | name    | shortname | completion | completionview | situationtags |
      | competvet | CVET1  | S1       | x     | MEDCHIR | SIT1      | 2          | 1              | y:1           |
    And the following "mod_competvet > plannings" exist:
      | situation | group | startdate        | enddate               | session  |
      | SIT1      | G1    | last Monday      | Monday next week      | SESSION1 |
      | SIT1      | G2    | Monday next week | Monday next fortnight | SESSION1 |
    And the following "mod_competvet > observations" exist:
      | student  | observer  | planning                                                   | context              | comment                     | privatecomment        | category         | status    |
      | student1 | observer1 | last Monday > Monday next week > SESSION1 > SIT1           | Context for this obs | Comment for this obs (obs1) | Private comment(obs1) | eval:observation | completed |
      | student1 | observer2 | last Monday > Monday next week > SESSION1 > SIT1           | Context for this obs | Comment for this obs (obs2) | Private comment(obs1) | eval:observation | completed |
      | student2 | observer1 | Monday next week > Monday next fortnight > SESSION1 > SIT1 | Context for this obs | Comment for this obs        | Private comment(obs1) | eval:observation | completed |
    And the following "mod_competvet > observations" exist:
      | student  | observer | planning                                         | context              | comment                     | progress         | improvement         | missing         | category      | status    |
      | student1 | student1 | last Monday > Monday next week > SESSION1 > SIT1 | Context for this obs | Comment for this obs (obs1) | Progress comment | Improvement comment | Missing comment | eval:autoeval | completed |
    And the following "mod_competvet > observation_criterion_value" exist:
      | observation                                                             | criterion | value                          |
      | last Monday > Monday next week > SESSION1 > SIT1 > student1 > observer1 | Q001      | 5                              |
      | last Monday > Monday next week > SESSION1 > SIT1 > student1 > observer1 | Q002      | Comment for this criteria Q002 |
      | last Monday > Monday next week > SESSION1 > SIT1 > student1 > observer1 | Q003      | Comment for this criteria Q003 |
      | last Monday > Monday next week > SESSION1 > SIT1 > student1 > observer1 | Q004      | Comment for this criteria Q004 |
      | last Monday > Monday next week > SESSION1 > SIT1 > student1 > observer1 | Q005      | Comment for this criteria Q005 |
      | last Monday > Monday next week > SESSION1 > SIT1 > student1 > observer2 | Q007      | 8                              |
      | last Monday > Monday next week > SESSION1 > SIT1 > student1 > observer2 | Q001      | 6                              |
      | last Monday > Monday next week > SESSION1 > SIT1 > student1 > observer2 | Q002      | Comment for this criteria Q002 |
      | last Monday > Monday next week > SESSION1 > SIT1 > student1 > observer2 | Q007      | 10                             |
    And the following "mod_competvet > observation_criterion_value" exist:
      | observation                                                            | criterion | value                                   |
      | last Monday > Monday next week > SESSION1 > SIT1 > student1 > student1 | Q001      | 5                                       |
      | last Monday > Monday next week > SESSION1 > SIT1 > student1 > student1 | Q002      | Comment for this criteria student  Q002 |
      | last Monday > Monday next week > SESSION1 > SIT1 > student1 > student1 | Q003      | Comment for this criteria Q003          |
      | last Monday > Monday next week > SESSION1 > SIT1 > student1 > student1 | Q004      | Comment for this criteria Q004          |
      | last Monday > Monday next week > SESSION1 > SIT1 > student1 > student1 | Q005      | Comment for this criteria Q005          |
      | last Monday > Monday next week > SESSION1 > SIT1 > student1 > student1 | Q007      | 8                                       |
      | last Monday > Monday next week > SESSION1 > SIT1 > student1 > student1 | Q002      | Comment for this criteria Q002          |
    And the following "mod_competvet > certification" exist:
      | student  | planning                                         | criterion | level | comment               | status        | supervisors          | validations                                                                           |
      | student1 | last Monday > Monday next week > SESSION1 > SIT1 | CERT1     | 50    | Comment for this cert | cert:seendone | observer1, observer2 | {observer1: "cert:seendone", "My comment"},{observer2: "cert:seendone", "My comment"} |
    And the following "mod_competvet > case" exist:
      | student  | planning                                         | fields                                                                                                                                                                                                                                                                                                                                                                                                                                                                                          |
      | student1 | last Monday > Monday next week > SESSION1 > SIT1 | "nom_animal": "Rebecca", "espece": "Chien", "race": "Caniche", "sexe": "F", "date_naissance": "2013-11-06", "num_dossier": "2502698046842591", "date_cas": "2024-04-07", "motif_presentation": "Boiterie", "resultats_examens": "Autres examens à faire", "diag_final": "Fracture", "traitement": "Chirurgie", "evolution": "Bon", "taches_effectuees": "Examen clinique, traitement", "reflexions_cas": "Cas complexe. Suivi nécessaire.", "role_charge": "Observateur"                        |
      | student1 | last Monday > Monday next week > SESSION1 > SIT1 | "nom_animal": "Brian", "espece": "Oiseau", "race": "Perroquet", "sexe": "M", "date_naissance": "2014-07-05", "num_dossier": "2502698068764105", "date_cas": "2023-06-10", "motif_presentation": "Vomissement", "resultats_examens": "Anomalie détectée", "diag_final": "Dermatite", "traitement": "Repos", "evolution": "Stable", "taches_effectuees": "Consultation, examen clinique, diagnostic, traitement", "reflexions_cas": "Réponse positive au traitement.", "role_charge": "Assistant" |
      | student1 | last Monday > Monday next week > SESSION1 > SIT1 | "nom_animal": "Michelle", "espece": "Oiseau", "race": "Canari", "sexe": "F", "date_naissance": "2012-01-21", "num_dossier": "2502698078674955", "date_cas": "2023-10-21", "motif_presentation": "Diarrhée", "resultats_examens": "Autres examens à faire", "diag_final": "Infection urinaire", "traitement": "Rien", "evolution": "Bon", "taches_effectuees": "Consultation, examen clinique", "reflexions_cas": "Cas complexe. Suivi nécessaire.", "role_charge": "Assistant"                  |
    And the following "mod_competvet > todo" exist:
      | student  | planning                                         | action               | targetuser | data                               |
      | student1 | last Monday > Monday next week > SESSION1 > SIT1 | 'eval:asked'         | observer1  | "context": "Context for this todo" |
      | student1 | last Monday > Monday next week > SESSION1 > SIT1 | 'certif:valid:asked' | observer1  | "criteria": "CERT1"                |
