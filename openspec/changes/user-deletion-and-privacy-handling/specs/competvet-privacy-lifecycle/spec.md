## Purpose

Align CompetVet with Moodle's privacy API by declaring the plugin's personal data correctly and defining its export and erasure behavior in a testable way.

## ADDED Requirements

### Requirement: CompetVet declares its personal data accurately to the privacy subsystem
The system SHALL accurately declare whether CompetVet stores personal data and which CompetVet data structures contain user-linked data.
The system SHALL NOT claim that no personal data is stored if CompetVet persists user-identifiable records.

#### Scenario: Privacy metadata matches stored data
- **WHEN** the privacy subsystem inspects CompetVet metadata
- **THEN** the declared metadata reflects the user-linked data actually stored by the plugin

### Requirement: CompetVet exports personal data for a requested user
The system SHALL support privacy export for user-linked CompetVet data that belongs to the requested user or identifies that user within CompetVet records, according to Moodle privacy rules.
The export SHALL be structured enough to let administrators understand which CompetVet records are included.

#### Scenario: User requests personal data export
- **WHEN** a privacy export is requested for a user with CompetVet records
- **THEN** the exported data includes the CompetVet personal data covered by the plugin's declared policy

### Requirement: CompetVet applies a defined deletion policy for privacy erasure requests
The system SHALL implement a privacy erasure behavior for CompetVet data linked to the requested user.
That behavior SHALL follow the documented policy for each CompetVet record type, including whether records are deleted, anonymized, or retained in a non-identifying form where lawful and operationally required.

#### Scenario: User requests personal data erasure
- **WHEN** a privacy erasure request is executed for a user with CompetVet records
- **THEN** CompetVet processes the user's data according to its documented deletion policy

### Requirement: Privacy operations must preserve plugin integrity
The system SHALL keep remaining CompetVet records technically readable after privacy export or erasure operations have been executed.
The system SHALL NOT leave the plugin in a state where ordinary views fail because a privacy operation removed user-identifying data.

#### Scenario: Remaining records stay readable after erasure
- **WHEN** a privacy erasure operation has removed or anonymized CompetVet user data
- **THEN** remaining CompetVet views still render without fatal errors
