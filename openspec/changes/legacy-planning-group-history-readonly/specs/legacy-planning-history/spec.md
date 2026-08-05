## Purpose

Preserve the readability and inspectability of historical plannings when their original Moodle group has been deleted, without letting the application offer incoherent editing actions.

## ADDED Requirements

### Requirement: Plannings with missing groups become read-only
The system SHALL expose a planning as read-only when its referenced group no longer exists.
The system SHALL prevent editing actions that depend on a live Moodle group for such a planning.
The system SHALL keep repair-oriented orphan-user actions limited to plannings whose referenced group still exists.

#### Scenario: Open a planning whose group no longer exists
- **WHEN** a user opens a planning that references a deleted or missing group
- **THEN** the planning is displayed in read-only mode

#### Scenario: Existing group keeps orphan-user repair workflow
- **WHEN** a planning still references a live Moodle group but some attached users are no longer members of that group
- **THEN** the system keeps that planning in the repairable orphan-user workflow rather than in historical read-only mode

#### Scenario: Student moved from group A to group B with existing data
- **WHEN** a student has existing CompetVet data on a planning for group A and is later moved to group B while group A still exists
- **THEN** the system treats that case as an orphan-user repair scenario, not as a missing-group historical scenario

### Requirement: Historical planning view remains explorable
The system SHALL continue to display the historical data already attached to a read-only planning, including observations, evaluations, certifications, and related planning content.
The system SHALL derive the displayed student list for such a planning from the users already attached to those planning-linked entities rather than from current group membership.

#### Scenario: View students on a historical planning
- **WHEN** a planning is read-only because its group is missing
- **THEN** the user can still inspect the planning data and the student list derived from attached planning records

### Requirement: Missing groups use a deterministic display label
The system SHALL display the live group name when the referenced Moodle group still exists.
The system SHALL display a fallback label in the form `Groupe inconnu (<groupid>)` when a planning references a missing group.

#### Scenario: No live group is available
- **WHEN** a planning references a missing group
- **THEN** the system displays `Groupe inconnu (<groupid>)`

### Requirement: Backup and restore preserve historical planning readability
The system SHALL preserve the data needed to keep historical plannings readable across backup and restore.
The system SHALL restore a planning with a missing group into the same read-only and fallback-label behavior unless a valid live group relationship exists again.

#### Scenario: Restore a backup containing historical plannings
- **WHEN** a backup containing plannings whose groups are missing is restored
- **THEN** those plannings remain readable with correct read-only behavior and fallback group labeling
