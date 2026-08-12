## Purpose

Ensure that CompetVet backup and restore preserve a coherent grid and criterion structure without unintended duplication, and that all restored data continues to reference the correct business entities.

## Requirements

### Requirement: Restore must preserve valid grid identity without unnecessary duplication
The system SHALL restore CompetVet grids in a way that preserves functional grid identity and avoids creating duplicate grids when an equivalent target grid is already meant to be reused.
The system SHALL define the reuse rule explicitly so administrators and tests can distinguish intentional reuse from accidental duplication.

#### Scenario: Restore reuses an eligible existing grid
- **WHEN** a CompetVet backup is restored into an environment where an eligible matching grid already exists according to the restore policy
- **THEN** the restored activity reuses that grid instead of creating a duplicate copy

### Requirement: Restore must preserve criterion uniqueness within each restored grid
The system SHALL avoid duplicating criteria inside one target grid during restore when the criterion is already represented there according to the restore matching policy.
The system SHALL keep parent-child criterion relationships coherent after remapping.

#### Scenario: Restore does not duplicate criteria in reused grid
- **WHEN** a restore maps activity data onto a grid that is reused during restore
- **THEN** the target grid contains one coherent copy of each restored criterion and its hierarchy

### Requirement: Restored activity data must reference the remapped grids and criteria consistently
The system SHALL ensure that restored situations, observations, certifications, and other grid-backed records point to the actual remapped target grids and criteria after restore.
The system SHALL NOT leave restored business records pointing to stale, missing, or duplicated criterion identities.

#### Scenario: Observation references restored criteria correctly
- **WHEN** an activity with observations is restored
- **THEN** the restored observation criteria levels and comments reference the criteria that belong to the restored activity's effective target grid

### Requirement: Repeated restores must remain stable
The system SHALL behave predictably when the same CompetVet backup is restored multiple times into the same site or course landscape.
Repeated restores SHALL NOT accumulate unintended extra copies of grids or criteria beyond what the explicit restore policy requires.

#### Scenario: Same backup is restored twice
- **WHEN** the same CompetVet backup is restored multiple times
- **THEN** the resulting grid and criterion structure remains consistent with the defined reuse and duplication policy

### Requirement: Backup and restore integrity must be validated for grid-backed workflows
The system SHALL verify backup/restore integrity not only for raw record counts but also for grid-backed evaluation and certification semantics.
The integrity checks SHALL cover at least grids, criteria, observations, and certifications.

#### Scenario: Restored certification workflow uses correct criteria
- **WHEN** a CompetVet activity with certification data is restored
- **THEN** the restored certification declarations and validations remain aligned with the intended restored certification criteria

### Requirement: Backup/restore behavior must be protected by regression tests
The system SHALL be covered by automated tests that detect accidental duplication of grids or criteria and broken remapping after restore.
The regression coverage SHALL include at least one restore into a fresh target and one restore into a target where grid reuse or duplication decisions matter.

#### Scenario: Tests detect duplication regressions
- **WHEN** backup/restore regression tests run
- **THEN** they fail if restored grids or criteria are duplicated contrary to the defined restore policy
