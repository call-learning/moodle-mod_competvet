## Context

See `proposal.md` for motivation. CompetVet currently creates observation records before a real evaluation is necessarily entered, and an observation can persist with only empty criterion levels. The code already has a low-level rule for empty grades via `observation_criterion_level::is_an_empty_level()`, but counters such as `get_evaluations::numberofobservations` and planning-level `has_user_data()` still treat raw observation presence as meaningful data. Observation deletion already cascades to linked comments and criterion records, which gives a safe base for purging whole empty observations once the emptiness rule is centralized.

## Goals / Non-Goals

**Goals:**
- Define one reusable server-side rule that decides whether an observation is empty.
- Ensure effective counters and planning data checks ignore empty observations without hiding unfinished observation records.
- Provide a purge path for existing empty observations created by mistake.
- Reuse existing cascading deletion behavior instead of inventing a parallel cleanup path.
- Keep observations with at least one real grade intact.

**Non-Goals:**
- Redesign the observation authoring workflow in this change.
- Delete partially meaningful observations that contain comments or context but also a real grade.
- Remove all historical not-started observations indiscriminately without checking their effective content.
- Introduce a brand new archive table for purged observations.

## Decisions

### Centralize emptiness detection in observation-domain logic
Decision: define an observation-level emptiness helper built from the existing grade-level emptiness rule, and reuse it everywhere counters or purge decisions need to know whether an observation is meaningful.

Rationale: the code already knows what an empty grade is, but not yet what an empty observation is. Reusing one derived rule avoids drift between reports, APIs, and cleanup code.

Alternatives considered:
- Re-implement emptiness checks independently in each API. Rejected because counters would diverge over time.

### Separate "ignore in counters" from "purge from storage"
Decision: make effective counting logic ignore empty observations regardless of whether the purge has already been run, while keeping unfinished empty observations available to observation read/list views. Keep purge as a separate explicit maintenance action.

Rationale: the user wants both correct behavior going forward and a cleanup path for existing data. If counting depends on purge having already happened, the bug would remain until maintenance is run. If visibility depends on counting, a newly created unfinished observation can disappear before the user has a chance to complete it.

Alternatives considered:
- Rely only on purge. Rejected because stale data would still skew results until cleanup is executed everywhere.
- Auto-delete observations opportunistically during reads. Rejected because hidden write side effects on read paths are risky and hard to audit.
- Hide all empty observations from read/list views. Rejected because an unfinished observation must remain available for completion until an explicit purge is requested.

### Use full observation deletion for purge
Decision: purge empty observations by deleting the observation record itself and relying on existing cascade cleanup in the observation persistent to remove dependent comments and criterion records.

Rationale: there is already an `after_delete()` cleanup path for observation dependencies. Reusing it keeps purge behavior coherent and minimizes new data-deletion code.

Alternatives considered:
- Delete only criterion levels while keeping the observation shell. Rejected because it leaves the invalid root record that still needs special handling everywhere else.

### Prefer effective-content rules over status-only rules
Decision: treat emptiness as a function of effective grades, not only of `status` or category.

Rationale: a not-started observation is often empty, but the real issue described by the user is "sans aucune note". A status-only rule would miss completed-but-empty records or over-delete records whose status was not updated correctly.

Alternatives considered:
- Purge all `STATUS_NOTSTARTED` observations. Rejected because status alone is an operational hint, not a reliable proof of emptiness.

## Risks / Trade-offs

- [Comments on empty observations] Some empty observations may contain comments or context text -> Mitigation: define purge around absence of usable grades and make the deletion scope explicit in tests and release notes.
- [Counter regressions] Several screens may each compute counts slightly differently -> Mitigation: identify every current count or summary flag and align them to the shared emptiness rule.
- [Performance of purge] Scanning large historical datasets may be costly -> Mitigation: design the purge process so it can target only candidate empty observations and reuse existing deletion logic.
- [False positives] A weak emptiness rule could delete meaningful draft-like data -> Mitigation: require the absence of any usable grade before purge and protect non-empty observations with regression tests.

## Migration Plan

1. Audit every place where observation presence or counts are exposed to users.
2. Introduce a shared observation-level emptiness rule derived from existing empty-grade semantics.
3. Update counters and summary flags to ignore empty observations without requiring prior cleanup.
4. Add a purge process for historical empty observations and reuse existing cascading deletion behavior.
5. Validate the behavior on datasets containing empty observations, graded observations, and mixed cases.
6. Add regression tests for purge authorization, deletion scope, and corrected counters.
