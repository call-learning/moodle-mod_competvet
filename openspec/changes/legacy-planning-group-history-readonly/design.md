## Context

See `proposal.md` for motivation. Plannings currently store a direct `groupid` reference and many read paths resolve the group name or members straight from Moodle's live `groups` tables. When that group is deleted instead of renamed, the planning loses both its label and its canonical student membership source. Several views, APIs, reportbuilder joins, and backup/restore paths still assume that the group exists. The requested behavior therefore spans readonly planning access, display fallbacks, derived student reconstruction, and backup/restore compatibility, but without adding new historical storage. This work also needs to remain compatible with the orphan-user repair logic introduced in commit `8ea3fd3e8b0be33224c308ca6331dd81fa68af6e`, which handles the narrower case where the planning's live group still exists.
The concrete user problem behind that older flow is the exceptional case where a student moves from group A to group B between semesters after already creating CompetVet data in group A; those data then appear to "disappear" unless the system exposes or repairs the orphaned-user state explicitly.

## Goals / Non-Goals

**Goals:**
- Prevent editing workflows from operating on plannings whose live group no longer exists.
- Provide a deterministic way to show historical students and group labels for those plannings.
- Keep reports and backup/restore coherent with the historical-planning behavior.
- Preserve a clear behavioral split between repairable orphan-user cases and true missing-group historical cases.

**Non-Goals:**
- Recreate deleted Moodle groups automatically.
- Reconstruct perfect historical group membership from outside CompetVet-owned data.
- Redesign unrelated planning UX for still-valid live groups.

## Decisions

### Use fallback behavior instead of storing deleted-group history
Decision: do not add new storage for deleted-group metadata; once a planning's live group no longer exists, expose it through readonly behavior and a deterministic fallback label.

Rationale: the chosen option explicitly avoids schema changes. The system can still remain coherent if it treats missing groups as a degraded historical mode, using the surviving `groupid` only for fallback display and using CompetVet-owned records to reconstruct the meaningful planning data.

Alternatives considered:
- Introduce `competvet_group_history`. Rejected to keep the change lighter and avoid additional persistence and restore complexity.
- Copy the group name into `competvet_planning`. Rejected for the same reason: it still introduces persistence changes that this option is trying to avoid.

### Missing live groups force planning readonly mode
Decision: derive a readonly planning mode whenever the referenced Moodle group no longer exists.

Rationale: editing a planning without a valid live group would produce ambiguous or partial behavior, especially for membership-dependent actions. Explicit readonly mode is safer and easier to reason about than allowing partial edits.

Alternatives considered:
- Allow edits except for group changes. Rejected because many planning operations implicitly depend on live membership and would still be inconsistent.

### Reuse orphan-user repair only while the live group still exists
Decision: keep the legacy/orphan-user repair flow for plannings whose live group still exists, and reserve the new historical readonly mode for plannings whose `groupid` no longer resolves to a live Moodle group.

Rationale: the old orphan-user fix and this new change solve adjacent but different problems. A planning with a valid group can still be repaired by moving or reattaching users; a planning with no live group cannot safely use that workflow and must degrade to readonly historical access instead.

Alternatives considered:
- Collapse both cases into one historical-mode workflow. Rejected because it would throw away a useful repair path for still-valid plannings.

### Derive historical students from attached planning records
Decision: build the displayed student set for a historical readonly planning from users already referenced by planning-linked entities such as observations, evaluations, certifications, todos, and case data.

Rationale: once the live group disappears, CompetVet-owned records are the most trustworthy surviving source for who actually interacted with that planning. This does not recreate the original membership perfectly, but it preserves the users who matter for the historical record.

Alternatives considered:
- Show no student list at all. Rejected because it would make historical planning data much harder to interpret.
- Try to infer membership from unrelated course enrolment state. Rejected because current enrolments do not represent the historical planning cohort reliably.

### Keep backup/restore aware of missing-group semantics
Decision: preserve readonly semantics after restore by re-evaluating whether the restored planning's `groupid` resolves to a valid live group, and otherwise applying the same fallback behavior.

Rationale: backup/restore already serializes `planning.groupid`; without explicit handling, restored plannings whose groups are absent could still break downstream reads. The fix is not to restore extra history, but to make every restored planning follow the same live-group-or-fallback resolution path.

Alternatives considered:
- Ignore missing-group semantics in backup/restore. Rejected because restored archives would remain fragile and inconsistent to read.

## Risks / Trade-offs

- [Incomplete historical membership] CompetVet-owned entities may not cover every original group member -> Mitigation: document that readonly student lists are derived from attached planning records and test the main covered entity types.
- [Readonly spread] Existing screens or reports may assume every planning with an id is editable -> Mitigation: audit key planning views, APIs, and report sources that expose planning/group data.
- [Restore ambiguity] A restored instance may contain a numeric `groupid` that happens to exist locally but does not represent the original historical group -> Mitigation: define restore behavior around live group existence only and validate the degraded fallback path explicitly in tests.
- [Sequencing dependency] The orphan-user repair behavior may not yet be present on the target main branch -> Mitigation: treat integration of commit `8ea3fd3e8b0be33224c308ca6331dd81fa68af6e` or its equivalent as a prerequisite before implementing this change.

## Migration Plan

1. Audit where planning group names, memberships, and editability are currently derived.
2. Ensure the orphan-user repair behavior from commit `8ea3fd3e8b0be33224c308ca6331dd81fa68af6e` or equivalent is integrated on the main branch first.
3. Introduce readonly planning detection and apply it consistently to planning display and editing flows.
4. Add historical student derivation for readonly plannings and wire the fallback group label behavior.
5. Extend backup/restore to preserve missing-group readonly semantics.
6. Add regression coverage for live, orphan-user-repairable, missing-group, and restored missing-group planning cases.
