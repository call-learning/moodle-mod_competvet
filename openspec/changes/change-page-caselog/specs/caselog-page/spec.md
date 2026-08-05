## Purpose

Define a Caselog page that supports concise and pedagogically useful clinical-case handover, with unified input, explicit length limits, and a distinct personal reflection.

## ADDED Requirements

### Requirement: The Caselog form is presented as one continuous page
The system SHALL present the Caselog creation and edit experience as one vertically scrollable page, without splitting the user through separate screens or steps.
The page SHALL keep the existing fields `Nom de l'animal`, `Espece`, `Numero de dossier`, `Date de la prise en charge` and `Role dans la prise en charge`.
The page SHALL place the completion actions at the bottom in this order: `Enregistrer le brouillon`, `Annuler`, `Valider`.

#### Scenario: Open a Caselog entry for editing
- **WHEN** a learner opens a Caselog entry in create or edit mode
- **THEN** the learner sees a single continuous page containing the retained identity fields, the clinical transmission section, the reflection section, and the three completion actions at the bottom

### Requirement: Active categories define the structure of new Caselog forms
The system SHALL continue to use `competvet_case_cat` to group Caselog fields by category.
The system SHALL allow a category to be marked active or inactive for form usage.
The system SHALL include only active categories in new Caselog create and edit forms.

#### Scenario: Create a new Caselog entry
- **WHEN** a learner opens a new Caselog form
- **THEN** the form renders only the categories currently marked active for Caselog data entry

#### Scenario: Edit a Caselog entry with only active categories
- **WHEN** a learner edits a Caselog entry whose data only belongs to active categories
- **THEN** the form renders the active-category structure only

### Requirement: Historical Caselog data remains readable
The system SHALL preserve the display of historical categories and fields already stored on existing Caselog entries, even when those categories are no longer active in new forms.
The system SHALL not require legacy categories or fields to remain active in order to render historical entries.

#### Scenario: Open a historical Caselog entry
- **WHEN** a learner or evaluator opens an existing Caselog entry containing data from inactive categories or legacy fields
- **THEN** the application displays those stored categories and fields so the historical case remains readable

### Requirement: The page explains the pedagogical goal of the Caselog
The system SHALL display a chapo explaining that the Caselog evaluates the ability to transmit a clinical case in a clear, synthetic and actionable way for a colleague taking over.
The system SHALL state that the learner must not rewrite the full dossier or a bibliographic review and that a separate personal reflection is expected.
The system SHALL display one example model for `Transmission clinique` and one example model for `Reflexion sur le cas`.

#### Scenario: Read the Caselog instructions
- **WHEN** a learner opens the Caselog page
- **THEN** the learner can read the pedagogical chapo and see separate example models for the clinical transmission and the personal reflection

### Requirement: The Caselog captures a bounded clinical transmission
The system SHALL replace the current `Diagnostic final` field with a multiline field titled `Transmission clinique - 300 mots maximum`.
The system SHALL show helper text telling the learner to write in a synthetic or telegraphic style and to include the clinically useful commemorative, anamnestic, clinical, paraclinical, treatment, follow-up and vigilance elements needed for handover.
The system SHALL limit the field to 300 words maximum.

#### Scenario: Enter a valid clinical transmission
- **WHEN** a learner enters a transmission of 300 words or fewer
- **THEN** the system accepts the text for draft save and final validation

#### Scenario: Exceed the clinical transmission limit
- **WHEN** a learner enters more than 300 words in `Transmission clinique`
- **THEN** the system blocks final validation and explains that the field is limited to 300 words maximum

### Requirement: The Caselog captures a separate personal reflection
The system SHALL provide a multiline field titled `Reflexion sur le cas - 300 mots maximum`.
The system SHALL show helper text explaining that this field is for personal clinical progression, including what the learner understood better, what was difficult, what still needs consolidation and which next actions would help progress.
The system SHALL limit the field to 300 words maximum.
The system SHALL keep `Role dans la prise en charge` unchanged.

#### Scenario: Enter a valid reflection
- **WHEN** a learner enters a reflection of 300 words or fewer
- **THEN** the system accepts the text for draft save and final validation as a reflection distinct from the clinical transmission

#### Scenario: Exceed the reflection limit
- **WHEN** a learner enters more than 300 words in `Reflexion sur le cas`
- **THEN** the system blocks final validation and explains that the field is limited to 300 words maximum

### Requirement: Draft and final submission are distinct
The system SHALL let the learner save a Caselog as a draft without marking it as validated.
The system SHALL let the learner cancel without applying the current changes.
The system SHALL let the learner validate the Caselog once the required content satisfies the page rules.

#### Scenario: Save a draft
- **WHEN** a learner clicks `Enregistrer le brouillon`
- **THEN** the system persists the current Caselog content and keeps the entry in draft state

#### Scenario: Validate the Caselog
- **WHEN** a learner clicks `Valider` and both bounded text fields satisfy the 300-word limit
- **THEN** the system persists the content and marks the entry as validated
