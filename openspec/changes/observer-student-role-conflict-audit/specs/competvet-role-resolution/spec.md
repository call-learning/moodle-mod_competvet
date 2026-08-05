## Purpose

Define how CompetVet resolves a user's effective role when direct roles on the activity and inherited roles from parent contexts coexist, so false student/observer conflicts are avoided.

## ADDED Requirements

### Requirement: Activity-level CompetVet roles override inherited parent-context roles
The system SHALL prioritize CompetVet role assignments made directly on the CompetVet activity over inherited parent-context roles when determining the effective CompetVet role of a user.
The system SHALL NOT report a student/observer conflict solely because a student role is inherited from the course, category, or system while an observer role is assigned directly on the CompetVet activity.

#### Scenario: Observer on activity and student on course
- **WHEN** a user inherits the `student` role from a parent context and is assigned `observer` directly on a CompetVet activity
- **THEN** CompetVet resolves that user's effective role on that activity from the direct activity assignment rather than flagging a conflict

### Requirement: Role-conflict audit targets student versus observer first
The system SHALL distinguish student/observer conflicts from student/teacher or teacher/observer combinations during the CompetVet role audit.
The system SHALL verify the reported conflict path against the actual effective-role resolution rules before presenting it as a CompetVet role problem.

#### Scenario: Teacher role is not the primary conflict under audit
- **WHEN** a reported CompetVet role conflict is investigated
- **THEN** the audit first verifies whether the problematic overlap is between `student` and `observer` rather than between `student` and teaching roles

### Requirement: CSV role import replaces stale CompetVet module-role assignments
The system SHALL remove existing CompetVet-managed role assignments from the module context before applying the role assignments described by a CSV import row.
The system SHALL avoid leaving obsolete module-level combinations such as `student` plus `observer` on the same user after repeated imports.

#### Scenario: Reimport module roles from CSV
- **WHEN** a CSV import updates CompetVet roles for a user on the activity
- **THEN** the module-context CompetVet role assignments are refreshed without accumulating stale role combinations

### Requirement: Missing-group behavior is audited separately from role conflicts
The system SHALL treat missing-group planning behavior as a separate audit topic from effective-role conflicts.
The system SHALL NOT conflate a planning degraded by a missing group with a role-resolution conflict unless both conditions are independently present.

#### Scenario: Missing group does not imply role conflict
- **WHEN** a planning becomes degraded because its group no longer exists
- **THEN** the system evaluates that condition separately from any student/observer role-resolution issue
