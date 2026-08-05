## Why

Editing CompetVet grids has already caused model corruption in the past, especially on the evaluation grid. Even though a patch was added later, the editing flow still needs to be validated and the protections strengthened so a future change cannot reproduce the same class of breakage.

## What Changes

- Explore the grid and criterion editing flow to identify points that can still break the data model or leave an incoherent state behind.
- Formalize guardrails on edit, sort, delete, and reparenting operations for criteria or options.
- Verify that grids used by the model, especially the evaluation grid, remain coherent after modification.
- Add regression coverage targeting the editing scenarios that historically caused problems or still present architectural risk.

## Capabilities

### New Capabilities
- `grid-editing-safety`: Defines how editing CompetVet grids and criteria must protect model integrity and prevent structural regressions.

### Modified Capabilities

## Impact

- Grid and criterion management APIs and webservices.
- Delete, update, sort, and reorganization rules for criteria.
- Grid-editing tests and model-integrity protections.
- Any guardrails around global grids or grids already used by situations and evaluations.
