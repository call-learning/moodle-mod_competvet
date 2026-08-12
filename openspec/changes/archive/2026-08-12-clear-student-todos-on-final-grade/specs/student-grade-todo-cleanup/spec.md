## Purpose

Define how TODOs associated with a student are cleaned up when a final grade is assigned, so the final grading state remains coherent with pending actions.

## ADDED Requirements

### Requirement: Final grade assignment clears associated pending todos
The system SHALL remove or close pending TODOs associated with the graded student for the planning that received the final grade.
The system SHALL trigger this cleanup when a final grade is successfully recorded for that student and planning.

#### Scenario: Assign a final grade to a student
- **WHEN** an evaluator successfully records a final grade for a student on a planning
- **THEN** the system clears the pending TODOs associated with that student for that planning

### Requirement: Todo cleanup is scoped to the graded context
The system SHALL scope the cleanup to TODOs linked to the graded student and the planning associated with the recorded final grade.
The system SHALL NOT remove unrelated TODOs for other students or other plannings.

#### Scenario: Grade one student among several
- **WHEN** a final grade is recorded for one student on a planning that also has TODOs for other students or other plannings
- **THEN** only the TODOs associated with the graded student and that planning are cleared

### Requirement: Final grade notification flow remains coherent with todo cleanup
The system SHALL keep the post-grade flow coherent when the final grade update triggers both student notification and TODO cleanup.
The system SHALL avoid leaving pending TODOs behind after the final grade flow completes successfully.

#### Scenario: Grade flow completes and notifies the student
- **WHEN** the final grade flow completes successfully and the student notification is queued or sent
- **THEN** the graded student's pending TODOs for that planning are no longer left pending
