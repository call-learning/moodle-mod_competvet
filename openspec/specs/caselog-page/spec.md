# caselog-page Specification

## Purpose

Define a single Caselog experience for clearly transmitting one or more clinical cases while separating concise handover information from personal clinical learning.

## Requirements

### Requirement: The Caselog form is presented as one continuous page
The system SHALL present the Caselog creation and edit experience as one vertically scrollable page, without splitting the user through separate screens or steps.
The page SHALL keep `Nom de l'animal`, `Espece`, `Numero de dossier`, `Date de la prise en charge concernée`, and `Mon rôle dans la prise en charge`.
The page SHALL place the completion actions at the bottom in this order: `Enregistrer le brouillon`, `Annuler`, `Valider`.

#### Scenario: Open a Caselog entry for editing
- **WHEN** a learner opens a Caselog entry in create or edit mode
- **THEN** the learner sees a single continuous page containing the retained identification fields, the clinical transmission section, the reflection section, and the three completion actions at the bottom

### Requirement: The initial tutorial and Caselog page explain the exercise
Before access to the case, the system SHALL display the tutorial title `Ajouter une transmission de cas clinique` and explain that the exercise is to synthesise one or more clinical cases for a colleague taking over, not to rewrite a complete dossier or a bibliographic review.
The Caselog page SHALL display this chapo:
`Cette section évalue votre capacité à transmettre un cas clinique, comme à un.e collègue, de façon claire, synthétique et exploitable : contexte, problème principal, éléments cliniques utiles, prise en charge réalisée, suites prévues et points de vigilance.`
The page SHALL explain that a separate personal reflection is expected.

#### Scenario: Read the Caselog instructions
- **WHEN** a learner reaches the tutorial or opens the Caselog page
- **THEN** the learner can read the validated exercise instructions, the pedagogical chapo, and the distinction between clinical transmission and personal reflection

### Requirement: The current form version defines new Caselog forms
The system SHALL continue to use `competvet_case_cat` to group Caselog fields by category.
The system SHALL organize categories and fields within an explicitly identified Caselog form version.
The system SHALL include only categories belonging to the current published form version when creating a new Caselog entry, while retaining `Espece` in the active identification fields.

#### Scenario: Create a new Caselog entry
- **WHEN** a learner opens a new Caselog form
- **THEN** the form renders the identification fields and only the categories belonging to the current published form version

### Requirement: Caselog form versions remain available for existing entries
The system SHALL support multiple Caselog form versions at the same time.
Each Caselog entry SHALL store the form version used to create it.
The system SHALL assign the current published form version to new entries.
Published form versions and their field definitions SHALL remain available after a newer version is published.
An existing entry SHALL be displayed and edited using the form version stored on that entry, without being silently converted to the current version.

#### Scenario: Create an entry with the current version
- **WHEN** a learner creates a new Caselog entry
- **THEN** the system assigns the current published form version and renders that version's fields

#### Scenario: Display an entry from an older version
- **WHEN** a learner or evaluator opens an entry created with an older form version
- **THEN** the system renders the fields and labels belonging to that stored version, even if a newer version is current

#### Scenario: Edit an entry from an older version
- **WHEN** a learner edits and saves an entry created with an older form version
- **THEN** the system continues using that version and preserves its version-specific values without migrating them to the current form

### Requirement: Historical Caselog data remains readable and safe during edits
The system SHALL preserve the display of historical categories and fields already stored on existing Caselog entries, even when those categories are no longer part of the current published form version.
The system SHALL not require legacy categories or fields to remain in the current version in order to render historical entries.
When an existing entry is edited, legacy values SHALL remain preserved unless the learner explicitly changes or removes them through an available control.

#### Scenario: Open a historical Caselog entry
- **WHEN** a learner or evaluator opens an existing Caselog entry containing data from inactive categories or legacy fields
- **THEN** the application displays those stored categories and fields so the historical case remains readable

#### Scenario: Edit an entry containing inactive legacy data
- **WHEN** a learner edits an existing Caselog entry containing inactive legacy values and saves the entry
- **THEN** the application preserves those legacy values and does not discard them because their categories are inactive

### Requirement: Caselog web flows preserve form versions without changing local/mobile contracts
The Caselog web display and edit flows SHALL resolve each entry using its stored form version.
New-entry creation SHALL select the current published version on the server; consumers SHALL NOT provide a form-version identifier to choose it.
The existing `local_competvet` and mobile-facing API contracts SHALL remain unchanged.
Version-independent lists and summaries SHALL use stable entry data and SHALL not require fields that exist only in one form version.

#### Scenario: Display entries from several versions in the web flow
- **WHEN** the web flow displays Caselog entries created with different form versions
- **THEN** each entry is rendered using the stored version's fields and values

#### Scenario: Create through an existing consumer API
- **WHEN** an existing consumer creates a Caselog entry without a form-version parameter
- **THEN** the server assigns the current published version and persists the entry without changing the consumer contract

### Requirement: The Caselog captures a bounded clinical transmission
The system SHALL replace the current `Diagnostic final` field with a multiline field titled `Transmission clinique (1200 caractères maximum)`.
The system SHALL show this helper text:
`L’objectif n’est pas de rédiger un dossier complet mais de vous exercer à synthétiser les informations essentielles. Listez ici, de façon hiérarchisée, uniquement les éléments décisifs pour la compréhension du cas et son suivi. Omettez les éléments anecdotiques.`
The system SHALL limit the field to 1200 characters maximum.

#### Scenario: Enter a valid clinical transmission
- **WHEN** a learner enters a transmission of 1200 characters or fewer
- **THEN** the system accepts the text for draft save and final validation

#### Scenario: Exceed the clinical transmission limit
- **WHEN** a learner enters more than 1200 characters in `Transmission clinique`
- **THEN** the system rejects the submission and explains that the field is limited to 1200 characters maximum

### Requirement: The Caselog captures a separate personal reflection
The system SHALL provide a multiline field titled `Réflexions et enseignements issus du cas (800 caractères maximum)`.
The system SHALL show this helper text:
`Listez ici - Ce que vous avez mieux compris, - Ce qui vous a mis en difficulté et que vous referiez différemment avec le recul, - Les points que vous devez consolider.`
The system SHALL limit the field to 800 characters maximum.
The system SHALL keep `Mon rôle dans la prise en charge` unchanged.

#### Scenario: Enter a valid reflection
- **WHEN** a learner enters a reflection of 800 characters or fewer
- **THEN** the system accepts the text for draft save and final validation as a reflection distinct from the clinical transmission

#### Scenario: Exceed the reflection limit
- **WHEN** a learner enters more than 800 characters in `Réflexions et enseignements issus du cas`
- **THEN** the system rejects the submission and explains that the field is limited to 800 characters maximum

### Requirement: Draft and final submission are distinct
The system SHALL let the learner save a Caselog as a draft without marking it as validated.
The system SHALL let the learner cancel without applying the current changes.
The system SHALL let the learner validate the Caselog once the required content satisfies the page rules.
The system SHALL enforce both character limits before persisting either a draft or a validated submission.

#### Scenario: Save a draft
- **WHEN** a learner clicks `Enregistrer le brouillon` with text fields within their character limits
- **THEN** the system persists the current Caselog content and keeps the entry in draft state

#### Scenario: Cancel changes
- **WHEN** a learner clicks `Annuler`
- **THEN** the system leaves the current changes unapplied

#### Scenario: Validate the Caselog
- **WHEN** a learner clicks `Valider` and both bounded text fields satisfy their character limits and the page's required fields
- **THEN** the system persists the content and marks the entry as validated
