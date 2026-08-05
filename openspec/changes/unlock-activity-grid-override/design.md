## Context

See `proposal.md` for motivation. CompetVet stores grid selection directly on the `situation` record through `evalgrid`, `certifgrid`, and `listgrid`, but those fields are currently skipped in the form definition and therefore not exposed as a normal corrective action in the activity configuration flow. Runtime reads for evaluations and certifications resolve criteria from those fields, with fallback to default grids, so changing a grid after data entry would alter the meaning of existing records. The codebase already contains some usage checks such as `utils::is_grid_used()` for grid deletion safety, but those checks are broader than the narrower question here: can one specific activity rebind its workflow to another grid without invalidating already entered data?

## Goals / Non-Goals

**Goals:**
- Allow correction of a wrongly selected activity grid without recreating the whole activity.
- Keep the override action explicit and available from the activity settings area.
- Define clear safety rules that stop destructive or semantically unsafe overrides.
- Reuse existing grid-resolution and usage concepts where possible.
- Keep post-override reads aligned with the new grid when the change is allowed.

**Non-Goals:**
- Let users freely swap grids after data has accumulated regardless of consequences.
- Automatically migrate historical evaluations or certifications from one grid structure to another.
- Redesign all global grid-management workflows in this change.
- Solve unrelated grid-editing safety problems already covered by other specs.

## Decisions

### Treat override as an activity-level rebinding, not a grid migration
Decision: a grid override only changes which grid the activity points to going forward; it does not attempt to translate existing records from the old grid to the new one.

Rationale: stored evaluations and certifications reference criteria from a specific grid. Generic migration between arbitrary grids is much riskier than the correction workflow requested here.

Alternatives considered:
- Auto-map old criteria to new criteria. Rejected because there is no reliable universal mapping and the change would become a data-migration project.

### Default to blocking when dependent data exists
Decision: the normal override path should succeed only while no dependent workflow data exists for the targeted grid usage, and otherwise block with a clear explanation.

Rationale: the user explicitly wants to avoid data loss. Blocking is safer than inventing implicit cleanup or partial migration behavior.

Alternatives considered:
- Always allow override and silently ignore old data. Rejected because it would make historical data inconsistent and misleading.
- Always delete dependent data automatically during override. Rejected because it is too destructive for a normal settings action.

### Distinguish workflow-specific dependencies
Decision: evaluation-grid overrides and certification-grid overrides must each inspect the data families that actually depend on that workflow, rather than relying on one generic “grid is used somewhere” check.

Rationale: a grid can be globally used elsewhere yet still be safe to replace for one specific activity if that activity has no dependent data. Conversely, one activity may already have dependent certification data even if no evaluations exist.

Alternatives considered:
- Reuse only `utils::is_grid_used()` as a hard block. Rejected because it answers global grid usage, not activity-specific override safety.

### Reserve destructive cleanup for an explicit secondary action
Decision: if the product later wants to support “remove data and change grid”, that must be a distinct destructive workflow with stronger confirmation and scoped cleanup, not the default override path.

Rationale: the user mentioned the possibility of removing data to change the grid, but also emphasized data-loss prevention. That calls for separating safe correction from destructive reset.

Alternatives considered:
- Bundle both safe override and destructive reset into one button. Rejected because it increases the chance of accidental data loss.

## Risks / Trade-offs

- [Too-strict blocking] Users may be prevented from correcting a grid even in cases they consider recoverable -> Mitigation: keep the rule explicit and leave room for a future separate destructive reset workflow.
- [Hidden dependencies] Some data families may depend on the grid indirectly, not only through obvious evaluation rows -> Mitigation: inventory workflow-specific dependencies in tests before implementation.
- [UI ambiguity] Users may not understand why one grid can be changed and another cannot -> Mitigation: surface workflow-specific blocking reasons in the override UI.
- [Stale caches or reads] Changing the situation grid may leave some request-level caches or follow-up reads inconsistent -> Mitigation: verify post-override reads and grid-dependent helpers in regression tests.

## Migration Plan

1. Inventory all activity-scoped data that depends on evaluation and certification grids.
2. Define the safe-override rule for each workflow type and the blocking criteria once dependent data exists.
3. Expose an override action in activity settings with clear permission and warning behavior.
4. Update reads so successful overrides use the newly selected grid consistently.
5. Add regression tests for a safe override before use and a blocked override after data exists.
6. If desired later, design a separate destructive reset workflow rather than widening the safe override path implicitly.
