## Context

See `proposal.md` for motivation. CompetVet already exposes detailed evaluation data through `get_evaluations`, student evaluation views, observation APIs, and a small `reportbuilder` layer. Those surfaces are centered on individual observations or raw criteria, not on a durable progression summary per student. The current codebase therefore has most source data needed for this feature, but it lacks a shared consolidation layer that can be reused consistently by reports, student-facing pages, and observer-facing workflows.

## Goals / Non-Goals

**Goals:**
- Define one server-side aggregation model for competency progression that can be reused across report, student, and observer surfaces.
- Reuse existing evaluation and criterion storage instead of introducing a parallel persistence model.
- Keep historical observations visible in the progression output.
- Keep access control aligned with existing CompetVet visibility rules.
- Leave room for multiple UI entry points while avoiding duplicated business logic.

**Non-Goals:**
- Redesign the full evaluation workflow or grading scale semantics.
- Replace existing observation-by-observation views.
- Introduce a global cross-course competency warehouse outside the current CompetVet context.
- Freeze the exact final UI layout for every consumer in this planning change.

## Decisions

### Build a shared progression aggregation service
Decision: introduce a dedicated server-side aggregation layer that computes progression states from existing observations, criteria, and activity context, then reuse that layer everywhere else.

Rationale: the same business meaning is needed in at least three places: staff reports, student self-follow-up, and observation preparation. Recomputing the logic independently in each UI or webservice would drift quickly.

Alternatives considered:
- Extend only `get_evaluations` with more fields. Rejected because it is shaped around a single planning and does not naturally support report-oriented or cross-planning consolidation.
- Compute everything directly in `reportbuilder`. Rejected because observer and student flows also need the same output outside report tables.

### Keep consolidation scoped to CompetVet activity data
Decision: compute progression within the CompetVet activity scope using the criteria and evaluations already attached to the relevant situations and plannings.

Rationale: this preserves current data boundaries and avoids introducing ambiguity about whether competencies should be merged across unrelated activities or contexts.

Alternatives considered:
- Aggregate globally across all CompetVet instances in a course or site. Rejected because the request is about actionable activity-level progression and this wider merge would require additional product rules.

### Expose one normalized progression status model
Decision: define a normalized status vocabulary for progression output, covering at minimum "not evaluated", "evaluated not acquired", and "acquired", with any finer distinctions derived from the same model.

Rationale: the user request is about understanding what is evaluated, acquired, and still pending. A normalized status model is easier to test, report, and expose consistently than raw grade values alone.

Alternatives considered:
- Expose only averages or last grades. Rejected because they do not answer whether a competency is still missing or objectively acquired.

### Reuse reportbuilder for the staff report, not as the source of truth
Decision: keep `reportbuilder` as the delivery mechanism for the pedagogical report, but make it consume the shared progression aggregation logic instead of embedding core rules in report SQL alone.

Rationale: reportbuilder is already present in the module and is the right staff-facing reporting surface, but business rules for progression should stay testable outside one report definition.

Alternatives considered:
- Add a bespoke ad hoc report page only. Rejected because it would duplicate capabilities already available in reportbuilder filters/export patterns.

### Preserve historical evidence by being tolerant on missing modern metadata
Decision: historical evaluations must still contribute to progression even if some newer display conveniences or categorisations are absent, as long as the criterion evidence can be resolved.

Rationale: the user explicitly wants progression visibility and existing code already has legacy compatibility concerns in nearby areas. Dropping historical evidence would make the new report untrustworthy.

Alternatives considered:
- Limit the feature to newly created evaluations only. Rejected because it would hide much of the actual student history.

## Risks / Trade-offs

- [Ambiguous acquisition rule] Existing grades may not map trivially to acquired vs not acquired in every grid -> Mitigation: centralize the rule, document it in code and cover it with regression tests before exposing it widely.
- [Criteria duplication across plannings] The same competency may appear through several observations or situations -> Mitigation: aggregate by stable criterion identity and define how repeated evidence updates the consolidated state.
- [Performance drift] Staff reports could aggregate many students and observations -> Mitigation: keep a reusable aggregation layer with clear filtering boundaries and add tests or profiling on realistic datasets during implementation.
- [UI inconsistency] Different consumers may want slightly different wording or emphasis -> Mitigation: share the underlying status model and let each surface adapt only presentation, not core rules.
- [Historical data gaps] Some legacy records may not align perfectly with current grids -> Mitigation: prefer degraded but visible reporting over silent exclusion, and log any cases that cannot be resolved cleanly.

## Migration Plan

1. Audit current sources of truth for criteria, grades, observations, and access control.
2. Define the normalized progression status model and the aggregation rules that derive it from existing evaluation records.
3. Introduce a shared API/service that returns progression data for one student and for report-oriented filtered collections.
4. Plug that shared service into a staff report surface, a student-facing progression view, and an observer-facing observation helper.
5. Validate historical compatibility on existing test fixtures and real representative data.
6. Add regression coverage for access control, status aggregation, and visibility in each consumer surface.
