## Why

CompetVet grade calculation and display assume that the activity grade item is configured in points mode. If that setting drifts to another grade type, some read and write paths no longer behave correctly.

## What Changes

- Enforce the situation or CompetVet activity grade setting so it remains in points mode whenever CompetVet grading functionality depends on it.
- Verify and correct paths where an incompatible grade item can remain in place or reappear.
- Add protection so grade reads and writes do not depend on an incoherent grade configuration.
- Add regression tests covering grade creation, update, and read behavior when the expected type is points.

## Capabilities

### New Capabilities
- `grade-points-enforcement`: Ensures that the grade configuration used by CompetVet remains compatible with the expected points-based behavior.

### Modified Capabilities

## Impact

- Creation and update of the CompetVet `grade_item`.
- Reading grade type and scale metadata through CompetVet APIs and views.
- Situation or activity configuration when grading is enabled.
- PHPUnit coverage around the gradebook and grade reads.
