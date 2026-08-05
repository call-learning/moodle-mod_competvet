## Context

See `proposal.md` for motivation. CompetVet writes its gradebook item through `local/grader::grade_item_update()`, which explicitly creates value grades with `GRADE_TYPE_VALUE` whenever the activity uses a positive numeric grade. At the same time, other code paths such as `competvet::get_grade_type_for()` and `get_grade_item()` read the existing grade item and branch on its current `gradetype`. If that grade item drifts to a non-points type while CompetVet still expects numeric grading, the module can read incompatible metadata or expose broken note behavior. The issue is therefore not only grade creation, but also preserving a coherent grade-item type across both write and read paths.

## Goals / Non-Goals

**Goals:**
- Keep CompetVet's grade item in a points-compatible mode whenever numeric grading is expected.
- Ensure grade creation, update, and read paths all rely on the same compatible grade-item configuration.
- Prevent silent breakage when the grade-item type has drifted away from the mode expected by CompetVet.

**Non-Goals:**
- Redesign Moodle gradebook behavior outside CompetVet.
- Generalize the change to every possible scale-based grading workflow in Moodle.
- Change unrelated letter-grade display rules beyond what is required for grade-item compatibility.

## Decisions

### Treat the CompetVet grade item as module-owned configuration
Decision: when CompetVet relies on numeric grading, its grade synchronization path is allowed to enforce a value-based grade item rather than merely trusting whatever grade-item type currently exists.

Rationale: the module already writes `GRADE_TYPE_VALUE` in its own grader logic. Making that ownership explicit is more coherent than allowing external drift to break CompetVet's own assumptions.

Alternatives considered:
- Leave the grade-item type entirely user-controlled. Rejected because the current bug is exactly that an incompatible setting breaks the module.

### Align grade reads with the enforced write configuration
Decision: make grade-type reads and downstream grade logic assume the same enforced points-compatible configuration as the grade writer.

Rationale: forcing the writer alone is not enough if read paths still interpret stale or incompatible grade-item states. The module needs one coherent grade mode across both directions.

Alternatives considered:
- Patch only the update path. Rejected because read-time breakage would remain possible.

### Fail in a controlled way when automatic correction is not possible
Decision: if a path encounters an incompatible grade-item state that cannot be safely normalized in context, it should fail or report the incompatibility explicitly rather than continuing silently.

Rationale: silent continuation makes the problem much harder to diagnose and can produce invalid grade behavior. A controlled failure is preferable to hidden corruption.

Alternatives considered:
- Best-effort fallback to any grade type. Rejected because CompetVet's grade logic is specifically built around numeric behavior.

## Risks / Trade-offs

- [Unexpected override] Forcing the grade-item type may override a manual gradebook change that an administrator expected to keep -> Mitigation: scope enforcement to the CompetVet-managed grade item and document the expected points requirement.
- [Read/write mismatch] Some paths may still read grade metadata before normalization happens -> Mitigation: audit all grade-item read paths and add regression coverage around both read and write flows.
- [Partial compatibility] Letter-grade or display settings may still depend on a valid numeric max grade -> Mitigation: keep tests focused on value-grade scenarios and verify read-side grade metadata remains coherent.

## Migration Plan

1. Audit every CompetVet path that reads or writes the module grade item.
2. Identify the exact points where an incompatible grade-item type can survive or reappear.
3. Add enforcement or normalization so the module grade item stays points-compatible when numeric grading is required.
4. Add guardrails for read paths so they do not proceed silently with incompatible grade-item metadata.
5. Add regression coverage for creation, normalization, reading, and updating of the grade item in points mode.
