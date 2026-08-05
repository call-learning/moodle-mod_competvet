## 1. Data model and Caselog schema

- [ ] 1.1 Add a persisted status for Caselog entries with at least `draft` and `validated`, plus an upgrade step that initializes legacy entries to `validated`
- [ ] 1.2 Add an `active` flag on `competvet_case_cat` and a migration strategy that distinguishes categories used for new forms from legacy categories kept for historical display
- [ ] 1.3 Update the default Caselog structure to retain the requested identity fields, replace `Diagnostic final` with `Transmission clinique - 300 mots maximum`, keep `Role dans la prise en charge`, and add `Reflexion sur le cas - 300 mots maximum` while preserving legacy categories and fields for backward readability
- [ ] 1.4 Extend Caselog metadata so the application can render the chapo, example blocks, helper text and 300-word limits from configuration

## 2. Caselog entry workflow

- [ ] 2.1 Replace the modal-based add/edit Caselog launcher with a dedicated single-page create/edit flow
- [ ] 2.2 Filter new Caselog create and edit forms so they only render active categories, while historical entry display keeps rendering stored inactive categories and fields
- [ ] 2.3 Implement the single vertically scrollable Caselog page with the retained fields, instructional content, examples, and bottom actions `Enregistrer le brouillon`, `Annuler`, `Valider`
- [ ] 2.4 Wire the save actions so `Enregistrer le brouillon` persists draft state, `Annuler` leaves the current changes unapplied, and `Valider` persists a validated entry

## 3. Validation and downstream adaptations

- [ ] 3.1 Implement shared word-count validation for `Transmission clinique` and `Reflexion sur le cas` in both the page UI and the server-side submission path
- [ ] 3.2 Update Caselog API and summary consumers that currently depend on removed fields such as `motif_presentation`, using retained data for labels and displays instead
- [ ] 3.3 Add or update automated coverage for category activation, backward display of legacy entries, draft versus validated behavior, 300-word limit enforcement, and the revised Caselog display flow
