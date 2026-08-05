## Why

Today, the evaluation or certification grid selected for a CompetVet activity is effectively locked after creation, which makes a configuration mistake hard to correct without recreating the whole activity. That leads to unnecessary activity duplication even though a simple correction should be possible when no dependent data exists yet.

## What Changes

- Allow an activity owner to correct the grid used by a CompetVet activity after creation.
- Add an explicit action in the activity settings to replace the evaluation or certification grid with another compatible grid.
- Block or strictly constrain that action when data already exists that depends on the current grid, to avoid silent data loss.
- Define the expected behavior when no evaluation, certification, or other grid-dependent usage exists yet, so a wrong initial choice can be corrected safely.
- Add regression coverage for the cases "no data so change allowed" and "data exists so change blocked or confirmed according to the chosen policy".

## Capabilities

### New Capabilities
- `activity-grid-override`: Allows the criteria or certification grid attached to a CompetVet activity to be replaced under safety conditions that prevent data loss.

### Modified Capabilities

## Impact

- Settings and configuration form for CompetVet situations or activities.
- Grid resolution by situation and planning for evaluation and certification.
- Usage checks for grids and criteria already present in `utils::is_grid_used()` and the business APIs.
- Evaluation and certification flows, and any stored data that depends on the grid attached to the situation.
- PHPUnit and possibly Behat coverage for allowed changes, refused changes, and data-loss guardrails.
