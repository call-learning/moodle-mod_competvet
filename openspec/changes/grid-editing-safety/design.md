## Context

See `proposal.md` for motivation. Grid editing currently flows through `manage_criteria` and the `criteria` API, which directly create, update, sort, and delete grids, criteria, and options. Some protections already exist, such as `can_delete()` checks and usage checks before deletion, and tests cover a subset of update and delete scenarios. However, the current flow still applies many mutations procedurally without an explicit model-level integrity contract, and the user reports that a past edit corrupted the evaluation-grid model before a later patch mitigated it. The backup/restore path is also part of the integrity surface because restore reuses grids by `idnumber` and then remaps criteria and dependent records onto the resulting grid ids. The change therefore needs to validate the current guardrails, identify remaining structural failure modes, and add stronger regression coverage around both risky edit paths and backup/restore remapping.

## Goals / Non-Goals

**Goals:**
- Identify and reduce the risk that grid editing can corrupt the underlying criterion model.
- Make structural safety expectations explicit for grid, criterion, and option mutations.
- Preserve working delete-protection behavior for used criteria and options.
- Strengthen regression coverage around the historically fragile evaluation-grid editing flow.
- Ensure backup/restore does not reintroduce grid corruption through incorrect grid or criterion remapping.

**Non-Goals:**
- Redesign the entire criteria editor UX.
- Replace the current grid and criterion persistence model with a new schema.
- Broaden the scope to unrelated grading or Caselog changes.

## Decisions

### Treat integrity validation as a first-class editing concern
Decision: explicitly harden the grid editing flow around structural invariants rather than relying only on scattered delete guards and ad hoc patches.

Rationale: current code mutates grids procedurally across several operations, and a previous production break indicates that localized fixes are not enough on their own. The system needs a clearer safety boundary around supported edit sequences.

Alternatives considered:
- Keep the current patch-only approach. Rejected because it does not provide confidence against adjacent regressions.

### Focus on the real risky mutation paths
Decision: prioritize update, delete, sort-order, option-edit, and parent-child structure changes as the main risk surface for corruption.

Rationale: these are the operations directly exposed through `manage_criteria` and `criteria`, and they are the places where the model can drift into an invalid or incoherent state.

Alternatives considered:
- Limit the work to delete protections only. Rejected because historical breakage may also come from updates, reorderings, or parent-child mutations.

### Use regression tests as the main guarantee against recurrence
Decision: extend automated coverage around the historically fragile evaluation-grid editing flows and explicitly verify post-edit readability and coherence.

Rationale: the change is fundamentally about preventing recurrence. Regression coverage is the most reliable way to keep future patches from reintroducing the break.

Alternatives considered:
- Rely on manual QA only. Rejected because the issue already escaped once and is too stateful to trust to informal checks alone.

### Include backup/restore in the integrity boundary
Decision: treat backup/restore of grids, criteria, and their dependent records as part of the same structural safety problem as live editing.

Rationale: restore currently deduplicates grids by stable identifier and then rebuilds criterion and downstream mappings. If those mappings are wrong, or if an existing grid has drifted structurally, restore can silently reconnect situations or observations to an incoherent model even when the edit API itself is safe.

Alternatives considered:
- Treat backup/restore as a separate concern. Rejected because the same grid invariants and historical corruption risks apply once restored data is put back into active use.

## Risks / Trade-offs

- [Unknown original failure mode] The exact historical corruption sequence may not be fully documented -> Mitigation: audit current edit paths broadly enough to cover adjacent structural hazards, not only the remembered patch.
- [Over-restriction] New safety guards could reject edits that are currently allowed and relied upon -> Mitigation: focus on demonstrably unsafe mutations and back changes with tests for supported flows.
- [Partial coverage] Existing tests may not exercise the specific multi-step edit sequences that caused prior corruption -> Mitigation: add regression cases that combine updates, deletes, ordering, and follow-up reads.
- [Restore-time remapping drift] Backup/restore may reconnect restored situations or observations to the wrong grid or criterion records when grids are deduplicated by `idnumber` -> Mitigation: audit restore mappings explicitly and add round-trip coverage for evaluation-grid backups.

## Migration Plan

1. Audit the current grid editing flow and identify the mutation paths most likely to corrupt the model.
2. Audit the backup/restore mapping flow for grids, criteria, and downstream records that depend on them.
3. Define or codify the structural invariants that edited or restored grids must continue to satisfy.
4. Add or tighten guards around unsafe mutations or restore assumptions where the current flow is too permissive.
5. Add regression coverage for the historically fragile evaluation-grid editing scenarios and for backup/restore round trips, then verify post-edit and post-restore readability.
6. Use those tests as the baseline for any future grid editor changes.
