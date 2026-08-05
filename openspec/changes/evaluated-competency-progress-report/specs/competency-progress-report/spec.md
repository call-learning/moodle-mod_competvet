## Purpose

Provide a consolidated and actionable view of a student's competency progression from existing evaluations so it can support pedagogical steering, self-tracking, and preparation for future observations.

## ADDED Requirements

### Requirement: The system provides a consolidated competency progression view per student
The system SHALL provide a consolidated progression view for each student over the competencies and criteria that are relevant to the student's CompetVet activity.
The consolidated view SHALL distinguish at least competencies that have not yet been evaluated, competencies evaluated but not yet acquired, and competencies considered acquired according to the server-side aggregation rules.

#### Scenario: Teacher opens a student's competency progression
- **WHEN** an authorized user opens the competency progression for a student
- **THEN** the system displays a consolidated list of competencies with their current progression state

### Requirement: The progression view is reusable in reporting and student-facing contexts
The system SHALL expose the same progression states through a reporting surface for staff and through a student-facing surface limited to the current student's own data.
The student-facing surface SHALL NOT expose another student's progression data.

#### Scenario: Student consults personal progression
- **WHEN** a student opens the progression view for their own CompetVet activity
- **THEN** the system displays only that student's consolidated competency progression

### Requirement: Observation workflows can surface missing or non-acquired competencies
The system SHALL make the consolidated progression state available from the observation workflow so an observer can identify competencies that are still missing or not yet acquired before recording a new observation.
The observation workflow SHALL present this information without requiring the observer to inspect each past evaluation individually.

#### Scenario: Observer prepares a new observation
- **WHEN** an authorized observer starts or reviews an observation workflow for a student
- **THEN** the system shows which competencies remain unevaluated or not yet acquired for that student

### Requirement: Progression aggregation remains compatible with historical evaluations
The system SHALL compute consolidated progression from existing historical evaluations without requiring legacy observations to be rewritten.
The system SHALL ignore missing modern display helpers only when necessary to keep historical competency evidence visible rather than dropping it from the progression output.

#### Scenario: Historical evaluations contribute to progression
- **WHEN** a student has historical evaluations created before the new progression report exists
- **THEN** those evaluations still contribute to the consolidated competency progression

### Requirement: Progression reporting is filterable and actionable for staff
The system SHALL provide a reporting surface that allows authorized staff to review competency progression across students using filters appropriate to CompetVet contexts such as activity, situation, group, or student when those dimensions are available.
The reporting surface SHALL make it possible to identify competencies that have never been evaluated or that remain not acquired.

#### Scenario: Staff identify missing evaluations
- **WHEN** an authorized staff member filters the progression report for a cohort, group, or activity
- **THEN** the report highlights students or competencies that remain unevaluated or not yet acquired

### Requirement: Access to progression data follows CompetVet role permissions
The system SHALL allow staff and observers with existing CompetVet viewing rights to access progression data for the students they are allowed to review.
The system SHALL deny access when a user requests progression data for a student outside their allowed visibility scope.

#### Scenario: Unauthorized user requests another student's progression
- **WHEN** a user without permission to view another student's CompetVet data requests that student's progression
- **THEN** the system denies access to the progression data
