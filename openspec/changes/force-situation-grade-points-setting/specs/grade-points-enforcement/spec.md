## Purpose

Ensure that the grade configuration used by CompetVet stays compatible with its calculations and grade writes by enforcing points-based behavior whenever the module depends on numeric grades.

## ADDED Requirements

### Requirement: CompetVet grade item stays compatible with points-based grading
The system SHALL keep the CompetVet grade item in a points-compatible grading mode whenever CompetVet features depend on numeric grades.
The system SHALL NOT leave an incompatible grade-item type in place when CompetVet needs to read or write numeric grades for the activity.

#### Scenario: Numeric grading is required
- **WHEN** a CompetVet activity uses features that depend on numeric grade reads or writes
- **THEN** the activity grade item is configured in a points-compatible mode

### Requirement: Incompatible grade-item settings are corrected or blocked
The system SHALL correct an incompatible grade-item configuration when it can safely do so through the CompetVet grade synchronization path.
The system SHALL otherwise block or clearly fail the incompatible path instead of silently continuing with broken grade behavior.

#### Scenario: Grade item has drifted to an incompatible type
- **WHEN** CompetVet encounters an activity grade item that is no longer compatible with points-based grading
- **THEN** the system restores or rejects that configuration in a controlled way

### Requirement: Grade reads and writes rely on a coherent grade-item type
The system SHALL return coherent grade information only when the underlying grade-item type matches the mode expected by CompetVet.
The system SHALL keep grade creation, grade updates, and grade-type reads aligned on the same enforced grade-item configuration.

#### Scenario: Read and write paths observe the same grade mode
- **WHEN** CompetVet reads grade-item metadata and later writes grades for the same activity
- **THEN** both operations use a mutually coherent points-based configuration
