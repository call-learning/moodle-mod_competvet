## Purpose

Define how editing CompetVet grids and criteria must protect model integrity so a grid change, especially on the evaluation grid, cannot again break stored data or the structure expected by the application.

## ADDED Requirements

### Requirement: Grid editing preserves structural integrity
The system SHALL preserve a valid grid and criterion structure after any supported grid editing operation.
The system SHALL prevent an edit operation from leaving a grid in a structurally inconsistent state for later reads or grading flows.

#### Scenario: Update an evaluation grid safely
- **WHEN** an authorized user edits a global evaluation grid
- **THEN** the resulting grid remains structurally valid for subsequent retrieval and grading usage

### Requirement: Dangerous criterion mutations are rejected or contained
The system SHALL reject or safely contain criterion mutations that would break the expected model, including unsafe deletions, unsafe reparenting, or inconsistent option updates.
The system SHALL preserve existing usage protections for criteria or options already referenced by situations, observations, or other model records.

#### Scenario: Attempt a dangerous criterion change
- **WHEN** an edit request would leave a criterion or option in an invalid or unsafe state
- **THEN** the system refuses or contains the change without corrupting the grid model

### Requirement: Grid editing regression paths are verified
The system SHALL provide regression coverage for the grid editing flows that are known to be risky, especially around evaluation grids, criterion updates, option updates, sort-order changes, and delete flows.
The system SHALL verify that supported edits keep later reads of the grid coherent.

#### Scenario: Run a regression edit sequence
- **WHEN** the known risky grid editing sequence is executed in automated verification
- **THEN** the system confirms that the grid remains readable and coherent after the edit sequence

### Requirement: Backup and restore preserve grid integrity
The system SHALL preserve a valid grid and criterion structure when CompetVet activity data is backed up and restored.
The system SHALL keep grid, criterion, and dependent record mappings coherent after restore, including cases where a restored grid matches an existing grid by stable identifier.

#### Scenario: Restore an activity that contains evaluation grids
- **WHEN** an activity backup containing CompetVet grids and criteria is restored
- **THEN** the restored situations, criteria, and dependent records continue to reference a structurally valid and coherent grid model
