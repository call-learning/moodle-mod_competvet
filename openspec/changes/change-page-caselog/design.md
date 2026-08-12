## Context

See `proposal.md` for motivation. The current Caselog implementation is data-driven from `data/default_cas_form.csv`, persisted through `competvet_case_cat` and `competvet_case_field`, rendered by `classes/form/case_form_add.php` and `classes/form/case_form_edit.php`, and opened through `amd/src/local/forms/case_form.js` with `core_form/modalform`. Categories currently serve both as the logical grouping of fields and as the page-by-page structure of the form. The current structure still exposes fields such as `Diagnostic final`, `Motif de presentation`, `Traitement`, `Evolution` and `Taches / procedures effectuees`, does not enforce the validated character limits, and only offers a single modal save action. The case list helper in `classes/local/api/cases.php` also derives a label from `motif_presentation`, so removing fields has downstream impact beyond the form itself.

## Goals / Non-Goals

**Goals:**
- Deliver a single continuous Caselog entry experience with the exact retained, replaced and added fields requested for the pedagogical use case.
- Enforce a 1200-character limit for `Transmission clinique` and an 800-character limit for `Réflexions et enseignements issus du cas`.
- Support distinct draft and final validation actions.
- Preserve existing Caselog data access patterns where possible while updating downstream consumers that rely on removed fields.
- Keep historical Caselog categories and fields readable and preserved during edits while constraining new forms to the new active category set; retain `Espece` in the active identification structure.

**Non-Goals:**
- Redesign unrelated CompetVet forms or generic modal helpers used by other features.
- Introduce a rich text editor, bibliography workflow or full clinical dossier authoring flow.
- Rework reporting beyond the minimum adaptations needed to remain coherent with the new Caselog fields.

## Decisions

### Replace the modal workflow with a dedicated Caselog page
Decision: move Caselog create/edit away from `core_form/modalform` to a dedicated page or equivalent full-page form controller.

Rationale: the requested UX needs one vertically scrollable experience with three end-of-page actions (`Enregistrer le brouillon`, `Annuler`, `Valider`). The current modal helper only models one primary save action plus default cancel behavior, which makes the requested workflow awkward and fragile.

Alternatives considered:
- Keep `ModalForm` and overload the footer with custom buttons. Rejected because it would fight the generic helper, complicate state handling and still constrain page layout.
- Keep the data-driven backend but open the form in a full page. Chosen because it isolates Caselog-specific UX changes without destabilizing other modal-based forms.

### Keep the data-driven field structure and version complete form schemas
Decision: continue using `competvet_case_cat` and `competvet_case_field` as the source of truth for Caselog structure, but organize them into immutable, explicitly identified form versions. Store the selected form-version identifier on each Caselog entry. Mark one version as current for new entries; do not mutate published versions in place.

Rationale: the current PHP forms already iterate over dynamic case fields, categories are already the grouping mechanism used page by page, and `cases::get_case_structure()` already carries `description` and `configdata`. Versioning the complete schema is safer than changing shared categories in place: old entries continue to resolve their original labels, ordering, metadata and values, while new entries resolve the current version.

Alternatives considered:
- Hardcode the complete page markup in a custom template. Rejected because it duplicates the existing form schema and makes future Caselog field evolution harder.
- Store the chapo, tutorial and field instructions only in language strings. Rejected because the field schema already owns most authoring metadata and should remain the source of truth for this form.
- Mutate shared categories and fields in place. Rejected because existing entries could no longer be rendered or edited according to the form they originally used.
- Keep only one current schema and transform old entries on read. Rejected because it risks data loss and makes API/mobile compatibility dependent on irreversible conversions.

### Select schemas by entry version
Decision: new entries use the current published form version, while existing entries use the version stored with the entry for display and editing. `Espece` remains part of the current identification fields even though it was omitted from the validated field sequence.

Rationale: form generation and historical rendering have different goals. New entries need the current pedagogical structure, while historical reads and edits must preserve meaning and stored values for legacy entries. Selecting by entry version avoids breaking old records and allows further versions to coexist.

Alternatives considered:
- Expose all versions as choices for every new entry. Rejected because new entries must consistently use the current published pedagogical structure.
- Remove old versions after migration. Rejected because historical entries must remain readable and editable.

### Add explicit Caselog entry status
Decision: add a persisted Caselog status that distinguishes at least `draft` and `validated`, and default pre-existing entries to `validated` during migration.

Rationale: the current `case_entry` persistent has no status field, so there is no reliable way to tell whether a saved entry is still a draft or has been formally validated. The requested UI semantics require the distinction to survive reloads and reporting.

Alternatives considered:
- Treat every save as final and only change button labels. Rejected because it would not implement the requested draft behavior.
- Infer draft state from field completeness. Rejected because completeness heuristics are unstable and not equivalent to explicit user intent.

### Adapt downstream list and reporting consumers away from removed summary fields
Decision: update case list and related summaries so they no longer depend on `motif_presentation`, and derive their label from still-retained data such as animal name, species and care date, with an optional short transmission excerpt if useful.

Rationale: the current list label uses `motif_presentation`, which is not part of the requested final form. Leaving that dependency in place would cause empty or misleading labels after the field removal.

Alternatives considered:
- Keep `motif_presentation` hidden only for list labels. Rejected because it adds a hidden authoring obligation that conflicts with the simplified pedagogy.

### Preserve version information without changing local or mobile API contracts
Decision: keep form-version selection and historical schema resolution inside the CompetVet web implementation. New entries always use the server-side current version; existing entries use their stored version. Do not add version-selection parameters or change the existing `local_competvet` or mobile-facing API contracts. Version-independent summaries continue using stable entry data rather than version-specific fields.

Rationale: the mobile and local consumers must remain backwards compatible and must not decide which form version is used. The web implementation can preserve historical schemas without requiring a client release or changing the existing service payloads.

Alternatives considered:
- Normalize every API response to the current schema. Rejected because it loses the original form semantics and can discard fields unknown to the current version.
- Change each client API to expose or select every form version. Rejected because it changes the existing local/mobile contract and makes mobile releases a deployment blocker.

### Enforce character limits on both client and server
Decision: validate the 1200-character maximum for `Transmission clinique` and the 800-character maximum for `Réflexions et enseignements issus du cas` in the page UI for immediate feedback and again on the server before persisting drafts or final validation.

Rationale: client-only counting is bypassable, while server-only counting delays feedback. Both layers are needed for a predictable Moodle form experience. The same normalization rule must be used in both layers so spaces, line breaks and Unicode characters produce consistent results.

Alternatives considered:
- Word-based limits. Rejected because the validated requirement is expressed in characters.
- Server-only validation. Rejected because it gives a poor writing experience on long free-text fields.

## Risks / Trade-offs

- [Workflow rewrite scope] Replacing a modal with a dedicated page touches JS launchers, routing and form submission paths -> Mitigation: isolate the change to Caselog entry flows and keep the generic modal helper untouched for other forms.
- [Data migration] Introducing a status field requires upgrading persisted entries safely -> Mitigation: add an upgrade step that initializes legacy entries as `validated` and keep backward-compatible reads during rollout.
- [Current-version drift] Selecting or changing the current version incorrectly could make new entries use the wrong schema -> Mitigation: define one published current version, make version selection explicit, and test new entries alongside older entries.
- [Schema version drift] A published version could be changed in place and invalidate old entries -> Mitigation: make published versions immutable and reject or separately publish edits as a new version.
- [Character count ambiguity] Counting characters consistently across line breaks and Unicode text can drift between browser and PHP implementations -> Mitigation: define one normalization rule and reuse it in both layers.
- [Downstream regressions] Local, `local_competvet`, or mobile consumers may assume one schema or removed fields still exist -> Mitigation: expose version metadata, preserve stable summary fields, and test mixed-version payloads and updates.

## Migration Plan

1. Add the new persisted entry status and run an upgrade step that marks existing Caselog entries as `validated`.
2. Create or identify the legacy form version and associate all existing Caselog entries with it.
3. Publish the new form version with its own categories and fields, without changing the legacy version's definitions.
4. Introduce the dedicated Caselog page/form flow, including the validated initial tutorial, chapo and field instructions; select the current version for new entries and the stored version for existing entries.
5. Update local display code, `local_competvet`, mobile-facing APIs, and list/report summaries to preserve version metadata and avoid dependencies on removed fields.
6. Test mixed-version reads, edits, drafts, final validation, and API payloads before publishing another version.
7. Roll back by selecting the previous published version for new entries and retaining all existing version associations; do not delete published schemas.
