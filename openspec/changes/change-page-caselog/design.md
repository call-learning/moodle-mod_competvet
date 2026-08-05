## Context

See `proposal.md` for motivation. The current Caselog implementation is data-driven from `data/default_cas_form.csv`, persisted through `competvet_case_cat` and `competvet_case_field`, rendered by `classes/form/case_form_add.php` and `classes/form/case_form_edit.php`, and opened through `amd/src/local/forms/case_form.js` with `core_form/modalform`. Categories currently serve both as the logical grouping of fields and as the page-by-page structure of the form. The current structure still exposes fields such as `Diagnostic final`, `Motif de presentation`, `Traitement`, `Evolution` and `Taches / procedures effectuees`, does not enforce a word-based limit, and only offers a single modal save action. The case list helper in `classes/local/api/cases.php` also derives a label from `motif_presentation`, so removing fields has downstream impact beyond the form itself.

## Goals / Non-Goals

**Goals:**
- Deliver a single continuous Caselog entry experience with the exact retained, replaced and added fields requested for the pedagogical use case.
- Enforce 300-word limits reliably for `Transmission clinique` and `Reflexion sur le cas`.
- Support distinct draft and final validation actions.
- Preserve existing Caselog data access patterns where possible while updating downstream consumers that rely on removed fields.
- Keep historical Caselog categories and fields readable while constraining new forms to the new active category set.

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

### Keep the data-driven field structure and version it through category activation
Decision: continue using `competvet_case_cat` and `competvet_case_field` as the source of truth for Caselog structure, and add an activation flag on categories so new forms only render active categories while historical entries remain readable with their stored legacy categories.

Rationale: the current PHP forms already iterate over dynamic case fields, categories are already the grouping mechanism used page by page, and `cases::get_case_structure()` already carries `description` and `configdata`. Extending category state is lower risk than deleting old categories or hardcoding every field in a brand-new renderer. This also gives a direct compatibility path: old entries still point to the same stored categories and fields.

Alternatives considered:
- Hardcode the complete page markup in a custom template. Rejected because it duplicates the existing form schema and makes future Caselog field evolution harder.
- Store the chapo and examples only in language strings. Rejected because the field schema already owns most authoring metadata and should remain the source of truth for this form.
- Duplicate legacy and new structures in parallel without an activation mechanism. Rejected because the application would have no clear rule to decide which categories belong in new forms.

### Filter creation and edition by active categories, not historical data reads
Decision: use category activation to filter the structure returned for new Caselog forms, while keeping historical read paths capable of rendering inactive categories and fields already linked to an entry.

Rationale: form generation and historical rendering have different goals. New entry and edit experiences need the current pedagogical structure, while read access must preserve meaning for legacy entries. Separating these concerns avoids breaking old records.

Alternatives considered:
- Keep inactive categories editable forever. Rejected because it would undermine the new form structure and keep obsolete pedagogical sections alive.
- Hide inactive categories everywhere. Rejected because it would break backward readability of existing cases.

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

### Enforce word limits on both client and server
Decision: validate the 300-word maximum in the page UI for immediate feedback and again on the server before persisting drafts or final validation.

Rationale: client-only counting is bypassable, while server-only counting delays feedback. Both layers are needed for a predictable Moodle form experience.

Alternatives considered:
- Character-based limits. Rejected because the requirement is expressed in words.
- Server-only validation. Rejected because it gives a poor writing experience on long free-text fields.

## Risks / Trade-offs

- [Workflow rewrite scope] Replacing a modal with a dedicated page touches JS launchers, routing and form submission paths -> Mitigation: isolate the change to Caselog entry flows and keep the generic modal helper untouched for other forms.
- [Data migration] Introducing a status field requires upgrading persisted entries safely -> Mitigation: add an upgrade step that initializes legacy entries as `validated` and keep backward-compatible reads during rollout.
- [Category activation drift] A wrong `active` flag could hide categories from new forms unexpectedly -> Mitigation: define explicit defaults, include upgrade seeding, and test active versus inactive category selection.
- [Word count ambiguity] Counting words consistently across punctuation and line breaks can drift between browser and PHP implementations -> Mitigation: define one normalization rule and reuse it in both layers.
- [Downstream regressions] Reports or lists may assume removed case fields still exist -> Mitigation: audit direct references to removed idnumbers and update them in the same change.

## Migration Plan

1. Add the new persisted entry status and run an upgrade step that marks existing Caselog entries as `validated`.
2. Add an `active` marker on Caselog categories and initialize legacy categories according to the new intended structure.
3. Update the default Caselog field structure and any seeding or regeneration utilities that derive case fields from CSV, without removing legacy categories needed for historical display.
4. Introduce the dedicated Caselog page/form flow, filtering create/edit forms by active categories while keeping read/update APIs compatible with existing case data where possible.
5. Switch add/edit entry launchers to the new page and update list/report summaries that depended on removed fields.
6. Roll back by restoring the previous launcher and category activation mapping only if no migrated data depends on the new status semantics.
