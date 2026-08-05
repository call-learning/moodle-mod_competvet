## Purpose

Define how the dynamic Caselog structure is cleaned up and prepared for later changes without breaking historical data readability, while distinguishing active categories for current forms from already stored legacy structure.

## ADDED Requirements

### Requirement: Caselog categories can be activated for current form usage
The system SHALL keep Caselog field grouping based on categories.
The system SHALL allow a Caselog category to be marked active or inactive for current form usage.
The system SHALL use the active category state to determine which categories appear in current Caselog forms after the structural cleanup.

#### Scenario: Build a new Caselog form
- **WHEN** the application builds the structure for a current Caselog entry form after cleanup
- **THEN** it includes only categories marked active for current form usage

### Requirement: Historical Caselog data remains readable
The system SHALL preserve the ability to display categories and fields already stored on existing Caselog entries, even when those categories are no longer active for new forms.
The system SHALL not require a historical category to remain active in order to render the values already linked to an existing entry.

#### Scenario: Read an existing entry with inactive categories
- **WHEN** a user opens an existing Caselog entry containing values linked to categories that are now inactive
- **THEN** the application displays those stored categories and values so the historical case remains readable

### Requirement: Form structure and historical rendering are resolved separately
The system SHALL distinguish between structure resolution for create or edit forms and structure resolution for historical entry display.
The system SHALL allow create and edit flows to follow the current active category set without removing legacy values from already stored entries.

#### Scenario: Edit with current structure while preserving history
- **WHEN** the application resolves the structure for current Caselog editing
- **THEN** it uses the active category set for the editable form while preserving historical values already stored on the entry

### Requirement: Category activation supports future Caselog redesigns
The system SHALL make category activation available as a stable mechanism that later Caselog changes can rely on when introducing new pedagogical structures.
The system SHALL keep the runtime field definitions under the current Caselog field model used by the application.

#### Scenario: Prepare a future Caselog redesign
- **WHEN** a later change introduces a new Caselog page structure
- **THEN** that change can activate the new categories for form usage without breaking the readability of entries created with older categories
