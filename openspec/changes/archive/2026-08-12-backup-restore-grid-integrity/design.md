## Context

See `proposal.md` for motivation. CompetVet backups include grids and criteria directly in the activity payload, and restore currently remaps situations to restored grid IDs while attempting to reuse existing grids by `idnumber`. Criteria are then reused by `(idnumber, gridid)`. This gives the module a basic anti-duplication mechanism, but the policy is implicit and narrow: it is not yet clearly specified whether reuse is always desirable, how local versus global grids should behave, and whether repeated restores can silently drift. The current backup/restore test mainly validates one nominal round-trip and record parity, but it does not assert the harder invariants around grid reuse, non-duplication, and stable remapping over multiple restores.

## Goals / Non-Goals

**Goals:**
- Make the grid and criterion restore policy explicit and testable.
- Prevent unintended duplication of grids and criteria during restore.
- Preserve correct remapping of observations, certifications, and other criterion-backed data.
- Extend backup/restore tests to cover repeated and non-trivial restore scenarios.
- Keep restore behavior understandable for administrators and future maintainers.

**Non-Goals:**
- Redesign the whole CompetVet grid model.
- Introduce a cross-site synchronization mechanism for grids outside restore.
- Solve unrelated runtime grid-editing problems not caused by backup/restore.
- Replace Moodle's backup/restore framework with a custom import/export system.

## Decisions

### Make grid reuse policy explicit rather than incidental
Decision: define an explicit policy for when restore reuses an existing grid and when it must create a distinct one, instead of relying on current `idnumber` behavior as an undocumented side effect.

Rationale: the current implementation already reuses grids by `idnumber`, but without a clear contract that can be defended or tested. That makes future changes risky.

Alternatives considered:
- Keep the current behavior undocumented and only add tests. Rejected because tests without policy can freeze accidental behavior rather than intended behavior.

### Validate remapping through business records, not only grid counts
Decision: test integrity through downstream records such as observation criteria levels and certification declarations, not only through the number of restored grids and criteria.

Rationale: a restore can preserve counts while still pointing business data at the wrong criterion identities.

Alternatives considered:
- Assert only that the same number of grids and criteria exist. Rejected because that misses broken mappings.

### Cover repeated restore scenarios explicitly
Decision: treat repeated restore of the same backup as a first-class regression scenario.

Rationale: duplication bugs often do not appear on the first restore into a clean target, but on the second or in a site that already contains structurally similar data.

Alternatives considered:
- Test only restore into a fresh course. Rejected because it leaves the main duplication risk uncovered.

### Keep backup/restore safety separate from grid-editing workflows
Decision: scope this change to backup/restore integrity only, even if some duplicate-grid symptoms resemble editing or import issues.

Rationale: backup/restore has its own mapping semantics and deserves isolated rules and tests.

Alternatives considered:
- Merge this work into broader grid-management safety changes. Rejected because it would blur responsibilities and slow down targeted hardening.

## Risks / Trade-offs

- [Wrong reuse rule] Reusing too aggressively can merge things that should stay distinct -> Mitigation: define the matching policy explicitly and test both reuse and non-reuse cases.
- [Hidden criterion drift] Parent/child relationships may survive counts while still mapping incorrectly -> Mitigation: assert hierarchy-aware criterion remapping through business records.
- [Restore-specific regressions] Tightening restore rules may alter existing backup compatibility behavior -> Mitigation: preserve valid current backups and add tests on representative fixtures before changing mapping logic.
- [Test complexity] Full restore scenarios are slower and more brittle than unit tests -> Mitigation: keep a focused matrix of high-signal restore cases rather than trying to exhaust every permutation.

## Migration Plan

1. Audit the current restore mapping behavior for grids and criteria, including existing implicit reuse rules.
2. Define the intended grid and criterion reuse policy for fresh and repeated restores.
3. Update restore logic where needed so it follows the intended policy consistently.
4. Extend backup/restore tests to validate both structural non-duplication and downstream business-record remapping.
5. Validate that restored evaluation and certification workflows still resolve the correct criteria after the changes.
