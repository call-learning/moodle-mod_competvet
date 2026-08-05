## Purpose

Make CompetVet robust when users referenced by historical data have been deleted from Moodle, without losing the operational readability of the remaining information or showing incoherent lists.

## ADDED Requirements

### Requirement: Student listings exclude deleted users from active planning membership
The system SHALL exclude deleted users from active student listings derived from current planning membership.
The system SHALL keep active planning lists usable after a student has been deleted from Moodle.

#### Scenario: Deleted student no longer appears in active planning list
- **WHEN** a student who belonged to a planning group is deleted in Moodle
- **THEN** the planning's active student list no longer presents that deleted student as a current student member

### Requirement: Historical CompetVet records remain readable after user deletion
The system SHALL keep historical CompetVet records readable when they reference a deleted user.
The system SHALL display a stable fallback identity for deleted or missing users instead of failing to render the related observation, certification, case, todo, or notification data.

#### Scenario: Observation references a deleted student
- **WHEN** an existing observation is viewed after its student has been deleted in Moodle
- **THEN** the observation remains readable and the missing user is shown through a fallback deleted-user representation

### Requirement: Deleted-user fallback must be applied consistently across CompetVet surfaces
The system SHALL use the same deleted-user fallback semantics wherever CompetVet reconstructs user information from stored user IDs.
The system SHALL avoid presenting a deleted user as an active participant while still allowing historical traceability where records remain visible.

#### Scenario: Same deleted user appears in multiple records
- **WHEN** the same deleted user is referenced by several CompetVet records
- **THEN** each surface uses a consistent deleted-user representation for that user

### Requirement: User deletion behavior must be explicitly defined for persisted CompetVet data
The system SHALL define, for each main CompetVet data family, whether user-linked records are preserved, anonymized, or deleted when a privacy-driven user data deletion is executed.
The system SHALL apply that policy consistently rather than leaving behavior implicit in raw foreign-key residue.

#### Scenario: Privacy deletion policy is applied to user-linked data
- **WHEN** a user data deletion request is executed for a user with CompetVet records
- **THEN** each affected CompetVet record type follows the documented preservation, anonymization, or deletion policy

### Requirement: Deleted-user handling must not be confused with group-deletion handling
The system SHALL keep user-deletion behavior independent from the separate planning/group-history rules defined elsewhere.
The system SHALL NOT require the group-deletion change to understand how deleted users are represented in historical CompetVet records.

#### Scenario: User is deleted while planning still exists
- **WHEN** a planning remains present but one referenced student user has been deleted
- **THEN** CompetVet applies user-deletion handling without depending on the separate group-deletion workflow

### Requirement: Deleted-user handling must be regression-tested on the end-user workflow
The system SHALL cover the user-deletion workflow with automated tests based on a real CompetVet scenario involving at least one evaluated student.
The regression workflow SHALL validate both the active student listing and the readability of existing application data after the deletion.

#### Scenario: Evaluated student is deleted
- **WHEN** a situation is created, a student is evaluated, and that student is later deleted
- **THEN** automated tests confirm the active student list is coherent and historical CompetVet data remains readable
