## Purpose

Allow a wrongly selected grid on a CompetVet activity to be corrected without recreating the activity, while preventing a late change from destroying or invalidating already entered evaluation or certification data.

## ADDED Requirements

### Requirement: Authorized users can override an activity grid before dependent data exists
The system SHALL allow an authorized user to replace the evaluation or certification grid assigned to a CompetVet activity when no dependent data has yet been recorded for that grid-backed workflow.
The override action SHALL be exposed from the activity configuration or parameters area rather than requiring a brand new activity.

#### Scenario: Correct wrong certification grid before use
- **WHEN** an authorized user opens a CompetVet activity that still has no certification data recorded
- **THEN** the user can replace the certification grid used by that activity

### Requirement: The system prevents unsafe grid changes once dependent data exists
The system SHALL prevent a grid override when existing evaluations, certifications, or other dependent records would become invalid, unreadable, or misleading because of the change.
The system SHALL explain why the override is blocked instead of silently failing.

#### Scenario: Override blocked after evaluations exist
- **WHEN** an authorized user attempts to replace an activity grid after dependent evaluation data has already been recorded
- **THEN** the system refuses the override and explains that existing data would be affected

### Requirement: Grid override safety rules must be defined per workflow type
The system SHALL apply safety checks appropriate to the workflow that uses the target grid, including at least evaluation and certification workflows.
The system SHALL NOT assume that the absence of one record type is sufficient if another dependent record type still makes the override unsafe.

#### Scenario: Certification override checks certification-specific usage
- **WHEN** an authorized user attempts to replace the certification grid for an activity
- **THEN** the system evaluates certification-linked data dependencies before allowing the change

### Requirement: Safe overrides update subsequent reads consistently
The system SHALL ensure that, after a permitted override, subsequent evaluation or certification reads for that activity resolve criteria from the newly selected grid.
The system SHALL NOT require users to recreate the activity to start using the corrected grid after the safe override succeeds.

#### Scenario: New grid is used after safe override
- **WHEN** an activity grid override succeeds before dependent data exists
- **THEN** later forms and reads for that workflow use the newly selected grid

### Requirement: Override actions must be explicit about data-loss behavior
The system SHALL clearly state whether an override is blocked, safe because no dependent data exists, or would require explicit destructive cleanup before proceeding.
The system SHALL NOT delete dependent data implicitly as a side effect of a normal grid override action.

#### Scenario: User is warned before any destructive path
- **WHEN** an override request would require removing existing dependent data to proceed
- **THEN** the system presents that as an explicit destructive operation rather than performing it automatically

### Requirement: Grid override behavior must be regression-tested
The system SHALL be covered by automated tests for at least one successful pre-data override and one blocked override after dependent data exists.
The regression coverage SHALL verify that the corrected grid is effectively used after a successful override.

#### Scenario: Tests cover allowed and blocked override paths
- **WHEN** automated tests run for activity grid override
- **THEN** they verify both the safe override path and the protected blocked path
