## Context

See `proposal.md` for motivation. The current Caselog runtime structure is built from `competvet_case_cat` and `competvet_case_field`, then consumed by `cases::get_case_structure()` and the generic case forms. Existing entries store values in `competvet_case_data` against field ids, which means historical readability depends on retaining access to the field and category definitions those values reference. A separate table, `competvet_case_fields`, appears to be used only in technical backup or restore flows rather than as the runtime source of form structure, so the design should focus on the singular tables first and keep ancillary flows coherent where needed. This change is intentionally a cleanup and preparation step before `change-page-caselog`, not the Caselog page redesign itself.

## Goals / Non-Goals

**Goals:**
- Introduce a backward-compatible way to clean and evolve Caselog structure over time.
- Make category activation the source of truth for which categories appear in new Caselog forms.
- Preserve the ability to read historical Caselog entries tied to legacy categories and fields.
- Establish an upstream foundation for the future `change-page-caselog` change.

**Non-Goals:**
- Redesign the Caselog page content itself in this change.
- Introduce the future pedagogical fields or final page flow planned in `change-page-caselog`.
- Replace the field model with a brand-new schema system.
- Remove or migrate historical Caselog values to a new storage model.

## Decisions

### Add category activation on the runtime category table
Decision: add an `active` flag to `competvet_case_cat` and treat it as the primary switch that determines whether a category belongs to the current Caselog form structure.

Rationale: categories are already the top-level grouping mechanism used by the runtime form builder. Adding state there is the smallest coherent change that supports structure evolution without redefining the entire field model.

Alternatives considered:
- Add activation on each field only. Rejected because the user intent is category-level versioning of page structure.
- Create a separate versioning table for form schemas. Rejected because it adds complexity before the current category model has been exhausted.

### Keep runtime structure resolution and historical display as separate concerns
Decision: resolve create/edit form structure from active categories, but resolve historical display from the categories and fields actually linked to stored entry values.

Rationale: new form composition and historical readability have different constraints. Using the same filtered query for both would either expose obsolete categories forever or hide historical data unexpectedly.

Alternatives considered:
- Use active categories for every path. Rejected because inactive historical categories would disappear from old entries.
- Ignore active categories in edit flows. Rejected because future form redesigns would not be enforceable.

### Treat `competvet_case_field` as the runtime field definition model
Decision: base the design on `competvet_case_field` as the runtime field definition table and treat `competvet_case_fields` as an ancillary technical concern unless a later implementation audit proves it participates in live structure resolution.

Rationale: current runtime paths, tests, and persistent models all load `competvet_case_field` directly. The plural table appears only in backup/restore paths and should not drive the primary architecture of this change.

Alternatives considered:
- Include `competvet_case_fields` in the main design as a parallel runtime source. Rejected because current code evidence does not support that.

### Prepare future changes without forcing them into this one
Decision: stop this change at structural cleanup, compatibility, and activation mechanics, leaving page redesign, word limits, and workflow changes to the downstream `change-page-caselog` work.

Rationale: this keeps the change narrowly scoped and ensures the dependency order remains clear: first clean and stabilize the structure, then consume that capability in the UI redesign.

Alternatives considered:
- Merge the structural and UI redesign work into one change. Rejected because it mixes migration risk with functional redesign and makes rollback harder.

## Risks / Trade-offs

- [Activation default errors] A wrong default for `active` could hide categories from new forms unexpectedly -> Mitigation: define explicit migration values and cover active/inactive behavior with tests.
- [Historical rendering regressions] The current entry rendering logic depends on the live structure definition, so removing or deactivating categories too early could suppress legacy values in old entries -> Mitigation: separate read paths for active-form structure and historical display, and stop relying on the current structure alone to reconstruct historical entries.
- [Structure cache inconsistency] The current Caselog structure cache is internally inconsistent and may never be reused correctly -> Mitigation: normalize cache key usage and define explicit invalidation when category activation or field structure changes.
- [Ancillary flow drift] Backup/restore logic may need updates to preserve category activation semantics, and the existing `competvet_case_fields` restore path should be audited against the real table schema -> Mitigation: audit backup/restore after the runtime path is updated and add coverage if activation must survive those flows.
- [Legacy field coupling] Some current list and summary paths still rely on legacy field idnumbers such as `motif_presentation` -> Mitigation: centralize or replace those assumptions during structural cleanup before downstream UI redesign.
- [Future coupling] A downstream UI redesign may assume more than category activation provides -> Mitigation: document this change as a prerequisite foundation, not the final Caselog UX change.

## Migration Plan

1. Add an `active` field on `competvet_case_cat` with a safe default for existing categories.
2. Define which current categories remain active for future forms and which become legacy-only.
3. Fix or replace any inconsistent structure caching so the active-category view of Caselog structure can be invalidated predictably.
4. Update runtime structure resolution so new forms query active categories, while historical entry rendering follows stored category and field links rather than only the current structure.
5. Audit and fix ancillary technical flows such as backup/restore for activation consistency and schema alignment if they serialize category structure.
6. Decouple list or summary paths from legacy field assumptions that would block structural cleanup.
7. Use this cleaned-up structural capability as the prerequisite base for downstream Caselog UI redesign work.
