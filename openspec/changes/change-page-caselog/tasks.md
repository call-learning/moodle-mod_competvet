## 1. Data model and Caselog schema

- [x] 1.1 Add a persisted status for Caselog entries with at least `draft` and `validated`, plus an upgrade step that initializes legacy entries to `validated`
- [x] 1.2 Introduce immutable Caselog form-version metadata and associate each entry with the version used to create it, including migration of existing entries to the legacy version
- [x] 1.3 Keep published versions and their category/field definitions immutable, and publish the new structure as a separate current version
- [x] 1.4 Update the current form version to retain `Nom de l'animal`, `Espece`, `Numero de dossier`, `Date de la prise en charge concernée` and `Mon rôle dans la prise en charge`, replace `Diagnostic final` with `Transmission clinique (1200 caractères maximum)`, and add `Réflexions et enseignements issus du cas (800 caractères maximum)` while preserving legacy definitions
- [x] 1.5 Extend Caselog metadata so each version can render its tutorial, chapo, field instructions and character limits from configuration

## 2. Caselog entry workflow

- [x] 2.1 Replace the modal-based add/edit Caselog launcher with a dedicated single-page create/edit flow
- [x] 2.2 Select the current published version for new entries and the stored version for existing entries, while retaining `Espece` in the current identification fields
- [x] 2.3 Implement the single vertically scrollable Caselog page with the retained fields, validated tutorial/chapo/instructions, and bottom actions `Enregistrer le brouillon`, `Annuler`, `Valider`
- [x] 2.4 Wire the save actions so `Enregistrer le brouillon` persists draft state, `Annuler` leaves the current changes unapplied, and `Valider` persists a validated entry
- [x] 2.5 Preserve legacy version-specific values when an existing Caselog entry is edited and saved

## 3. Validation and downstream adaptations

- [x] 3.1 Implement shared character-count validation for `Transmission clinique` (1200 characters) and `Réflexions et enseignements issus du cas` (800 characters) in both the page UI and the server-side submission path
- [x] 3.2 Keep the existing `local_competvet` and mobile-facing API contracts unchanged; select the current version server-side for new entries and preserve stored versions internally for web display and editing
- [x] 3.3 Update Caselog list and summary consumers that currently depend on removed fields such as `motif_presentation`, using retained version-independent data for labels and displays instead
- [x] 3.4 Add or update automated coverage for mixed form versions, backward display and edit preservation of legacy entries, draft versus validated behavior, 1200/800-character limit enforcement, tutorial/chapo content, API payloads, and the revised Caselog display flow
