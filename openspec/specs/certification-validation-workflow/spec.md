# certification-validation-workflow Specification

## Purpose

Define a certification-validation workflow that handles observer rejections correctly and returns the certification to pending validation until it is later confirmed through a valid completion path.

## Requirements

### Requirement: Observer rejection prevents final certification

The system SHALL treat an observer rejection as a non-validating outcome for the certification.
The system SHALL NOT expose a certification as finally validated while at least one current observer validation records a rejection outcome.

#### Scenario: Observer rejects a certification

- **WHEN** an invited observer records a rejection outcome for a certification
- **THEN** the certification remains in a non-validated state

### Requirement: Rejected certifications return to validation workflow

The system SHALL return a rejected certification to a state that requires validation again.
The system SHALL make the validation workflow actionable again after a rejection so the certification can be reviewed another time.

#### Scenario: Rejected certification becomes pending again

- **WHEN** a certification has been rejected by an observer
- **THEN** the certification is shown as waiting for validation rather than as completed

### Requirement: Validation todos follow the effective certification state

The system SHALL keep or recreate the certification-validation todos required to continue the workflow after a rejection.
The system SHALL only close validation todos when the certification is effectively in a completed validation state for the current cycle.

#### Scenario: Rejection does not silently close the workflow

- **WHEN** an observer records a rejection outcome
- **THEN** users still have an actionable validation workflow for that certification

### Requirement: Revalidation can complete after a rejected attempt

The system SHALL allow a certification that was previously rejected to be submitted and validated again in a later cycle.
The system SHALL expose the certification as validated only after the later validation state satisfies the workflow's completion rules.

#### Scenario: Certification is validated after rework

- **WHEN** a previously rejected certification is submitted again and receives the required validating outcome
- **THEN** the system exposes that certification as validated

### Requirement: Certification status stays coherent in reports and client consumers

The system SHALL expose the corrected certification state consistently in reporting surfaces and other client consumers that read certification status.
The system SHALL NOT display a rejected certification as validated in reports or downstream clients once the server-side workflow marks it as pending revalidation.

#### Scenario: Report reflects rejected certification correctly

- **WHEN** a certification has been rejected and returned to pending validation
- **THEN** reporting and client-facing certification data expose it as non-validated and pending follow-up
