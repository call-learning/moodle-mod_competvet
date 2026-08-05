## Context

See `proposal.md` for motivation. Certification state is currently derived from a combination of declaration status, validation presence, and a few boolean flags built in `certifications::get_certification()` and `get_certifications_by_status()`. The current implementation collapses observer feedback too aggressively: any validation record marks `hasvalidations` as true, the boolean rejection flags are overwritten while iterating validations, and the observer event that reacts to a completed validation closes all validation todos for the declaration regardless of whether the outcome was confirming or rejecting. This makes a rejected certification look effectively finished instead of sending it back into a pending validation loop. The same status data is also consumed by report-oriented surfaces and by client applications, so the correction must stay coherent beyond the immediate certification form.

## Goals / Non-Goals

**Goals:**
- Make rejection outcomes block final certification status.
- Keep certification validation todos aligned with the real state of the current validation cycle.
- Allow a rejected certification to be submitted again and later reach a valid completed state.
- Add regression coverage for the full reject-then-revalidate path.
- Ensure reporting outputs and mobile-facing consumers reflect the same corrected certification state.

**Non-Goals:**
- Redesign the certification UI or wording for every status label.
- Change unrelated observation or grading workflows.
- Introduce a brand new certification persistence model.

## Decisions

### Treat validation state as an aggregate, not as the last iterated flag
Decision: compute certification validation state from the full set of current validation outcomes rather than from mutable booleans overwritten during iteration.

Rationale: the current loop-based flag assignment can misrepresent multi-observer outcomes and can expose a certification as validated even when a rejection exists. An explicit aggregate model makes the workflow rules testable and stable.

Alternatives considered:
- Keep the current booleans and patch a single branch. Rejected because the bug comes from the aggregation model itself, not only one conditional.

### Reopen or preserve validation work after rejection
Decision: ensure a rejection does not close the certification-validation workflow, and that the system leaves actionable todos for the next validation cycle.

Rationale: today the observer completion hook closes validation todos for the declaration on every completed validation event. That behavior is correct for successful completion but wrong for rejection because the declaration still requires follow-up validation.

Alternatives considered:
- Leave todos closed and rely on manual reinvitation. Rejected because it makes the workflow inconsistent and easy to forget operationally.

### Keep revalidation within the existing declaration model
Decision: support the reject-then-revalidate cycle by tightening status and todo logic around the existing declaration and validation records rather than inventing a separate retry entity.

Rationale: the existing model already has declarations, observer associations, and validation records. The problem is workflow interpretation, not the absence of storage primitives.

Alternatives considered:
- Create a new retry or revision table. Rejected because it is heavier than needed for the requested behavior and would broaden the scope significantly.

### Keep report and mobile consumers on the same server-side truth
Decision: treat report outputs and mobile-consumed certification payloads as part of the same behavior contract, and validate them against the corrected server-side status rules.

Rationale: if the certification form is corrected but reports or mobile consumers still interpret the old status shape, the workflow will remain inconsistent in practice. The safest approach is to verify every consumer that depends on `get_certifications()` or related status derivations against the same aggregate rules.

Alternatives considered:
- Limit the change to the form workflow only. Rejected because it would leave contradictory states in reports and external clients.

## Risks / Trade-offs

- [Historical validations contaminate the current cycle] Older validation records may be mixed with later resubmissions -> Mitigation: explicitly define which validation records count for the current declaration state and cover that rule in tests.
- [Todo churn] Reopening validation work may create duplicate or stale todos -> Mitigation: reuse existing per-declaration todo matching where possible and assert deduplication in regression tests.
- [Behavior change in existing views] Lists that currently show rejected certifications as effectively done will move them back to waiting -> Mitigation: keep the spec focused on intended workflow behavior and verify the affected status buckets in automated tests.
- [Client drift] Mobile or report consumers may rely on the previous interpretation of certification states -> Mitigation: document the behavior change in changelog material and explicitly validate report/client-facing outputs during implementation.

## Migration Plan

1. Audit the current declaration-status and validation-status aggregation rules.
2. Define the effective rules for rejection, waiting, and final validation within one declaration lifecycle.
3. Update todo-closing behavior so only truly completed validation cycles are closed.
4. Validate every reporting or client-facing certification surface that depends on the same status derivation.
5. Add regression coverage for accepted, rejected, and rejected-then-revalidated certification flows.
6. Record a changelog note so mobile validation or adaptation is not missed when shipping the change.
