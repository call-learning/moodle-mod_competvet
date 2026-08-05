## Why

Today, when an observer rejects a certification, the workflow can still leave that certification in a finalized state or clear the associated validation todos. This prevents the certification from being clearly returned to a waiting-for-validation state and breaks the expected retry cycle after rejection.

## What Changes

- Correct certification global-status aggregation so an observer rejection cannot be interpreted as a validated certification.
- Return an observer-rejected certification to an `a faire valider` state so it can be submitted for validation again.
- Adjust validation-todo handling so a rejection does not incorrectly close the validation workflow.
- Ensure that the corrected state is also shown correctly in reports that expose certifications.
- Add regression coverage for accepted validation, rejected validation, and later revalidation scenarios.
- Add a changelog note indicating that the mobile application may require review or adaptation.

## Capabilities

### New Capabilities
- `certification-validation-workflow`: Manages the lifecycle of a certification across declaration, observer validations, rejection, and return to pending validation.

### Modified Capabilities

## Impact

- Local certification API and global-status calculation.
- Observers and todo logic tied to certification validations.
- Forms, views, and reports that display certification validation state.
- PHPUnit coverage for validation, observer rejection, and resubmission flows.
- Version coordination and changelog notes for mobile consumers of those states.
