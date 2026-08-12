## Purpose

Allow CompetVet to ignore and clean up empty evaluation observations so indicators remain reliable and observation data stays usable without deleting observations that contain real evaluation evidence.

## ADDED Requirements

### Requirement: The system identifies empty observations using a stable server-side rule
The system SHALL classify an observation as empty when it contains no usable evaluation grade according to the server-side grade emptiness rules.
The system SHALL apply the same emptiness rule consistently across purge operations and observation counters.

#### Scenario: Observation contains only empty grades
- **WHEN** an observation contains only grades that are considered empty by the server-side grading rules
- **THEN** the system classifies that observation as empty

### Requirement: Empty observations are excluded from observation counters
The system SHALL NOT count empty observations as completed or effective observations in user-facing counters, summaries, or planning indicators.
The system SHALL keep non-empty observations counted normally.
The system SHALL distinguish effective observation counts from observation visibility. Excluding an empty observation from an effective counter SHALL NOT by itself remove it from observation lists or unfinished-observation views.

#### Scenario: Mixed empty and non-empty observations
- **WHEN** a student has both empty observations and non-empty observations on the same planning
- **THEN** only the non-empty observations contribute to observation counters and status indicators

#### Scenario: Newly created empty observation remains visible
- **WHEN** an observation has been created but contains only empty grades and has not been explicitly purged
- **THEN** the observation remains available to the relevant observation view or read API
- **AND** it is not counted as an effective graded observation

### Requirement: The system provides a purge process for empty observations
The system SHALL provide a process that can remove observations classified as empty after they were created by mistake or abandoned.
The purge process SHALL remove the empty observation together with any dependent observation records that no longer have meaning once the empty observation is deleted.
The system SHALL NOT rely on counter filtering as a substitute for display or cleanup. Empty observations remain present until the explicit purge process is run.

#### Scenario: Purge removes an empty observation
- **WHEN** the purge process targets an observation classified as empty
- **THEN** the system deletes that observation and its dependent observation data

### Requirement: Purge must preserve observations that contain effective evaluation data
The system SHALL NOT purge an observation that contains at least one usable evaluation grade.
The purge process SHALL leave non-empty observations unchanged even if they also contain optional empty subparts.

#### Scenario: Purge skips a graded observation
- **WHEN** the purge process inspects an observation that contains at least one usable grade
- **THEN** the system keeps that observation and does not delete it

### Requirement: Empty-observation handling remains compatible with historical data
The system SHALL evaluate existing historical observations with the same emptiness rule without requiring data migration before the counters and purge process work.
The system SHALL avoid turning historical empty observations into visible counted observations once the new rule is applied.

#### Scenario: Historical empty observation is no longer counted
- **WHEN** historical data contains an observation with no usable grades
- **THEN** the system excludes that observation from effective observation counts

### Requirement: Empty-observation handling respects access and audit boundaries
The system SHALL expose the purge process only to users or execution paths already allowed to manage observation data.
The system SHALL deny ordinary read-only users any ability to purge observations through this feature.

#### Scenario: Unauthorized user attempts purge
- **WHEN** a user without observation-management rights attempts to trigger the purge process
- **THEN** the system denies the purge request
