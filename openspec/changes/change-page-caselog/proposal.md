## Why

The Caselog page should better reflect the pedagogical goal of the casebook: transmitting a clinical case clearly, concisely, and in a way that is actionable for a colleague. This change is needed to evolve the input structure toward a new pedagogical layout without losing backward compatibility with older categories and fields that are already stored.

## What Changes

- Keep the existing identification information unchanged: `Nom de l'animal`, `Espece`, `Numero de dossier`, and `Date de la prise en charge concernée`. Keep `Mon rôle dans la prise en charge` as part of the entry.
- Introduce versioned Caselog structures that keep category-based grouping while allowing several immutable form versions to coexist.
- Replace the current `Diagnostic final` field with a `Transmission clinique` text block limited to 1200 characters, with the validated helper text explaining the expected content.
- Add a second text block, `Réflexions et enseignements issus du cas`, limited to 800 characters, with the validated helper text focused on personal clinical progression.
- Update the initial tutorial, before access to the case, with the title `Ajouter une transmission de cas clinique` and the validated explanation of the exercise.
- Add the validated chapo explaining the goal of the casebook and clearly separating clinical transmission from personal reflection.
- Replace the current 3-screen workflow with a single vertically scrollable page.
- Group the end-of-entry actions at the bottom of the page with the buttons `Enregistrer le brouillon`, `Annuler`, and `Valider`.
- Associate each Caselog entry with the form version used to create it. New entries use the current published version, while existing entries continue to use their own version for display and editing.
- Preserve the readability and editability of old categories and old fields on historical entries without converting them silently to the current version.
- Keep form versions and their field definitions available to local display code, `local_competvet`, and the mobile application through the relevant APIs.

## Capabilities

### New Capabilities
- `caselog-page`: Defines the content, structure, backward-compatibility, and input requirements of the Caselog page on a single screen.

### Modified Capabilities

## Impact

- OpenSpec artifacts describing the expected Caselog page behavior.
- Tables `competvet_case_cat` and `competvet_case_field`, which drive the dynamic Caselog structure, plus the Caselog entry persistence needed for draft and validated status.
- Persisted form-version metadata and the association between each entry and its form version.
- Form and validation logic in the `competvet` module for clinical case entry.
- Templates, styles, and page organization for the Caselog interface.
- Any draft-save, cancel, and validation handling tied to the unified page.
- Rendering of historical entries so inactive categories and fields remain visible.
- Local, `local_competvet`, and mobile API consumers that read or edit Caselog entries across multiple form versions.
