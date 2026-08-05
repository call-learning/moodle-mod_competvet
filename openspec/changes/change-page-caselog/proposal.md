## Why

The Caselog page should better reflect the pedagogical goal of the casebook: transmitting a clinical case clearly, concisely, and in a way that is actionable for a colleague. This change is needed to evolve the input structure toward a new pedagogical layout without losing backward compatibility with older categories and fields that are already stored.

## What Changes

- Keep the existing identification information unchanged: `Nom de l'animal`, `Espece`, `Numero de dossier`, and `Date de la prise en charge`.
- Introduce a new Caselog structure that keeps category-based grouping in `competvet_case_cat` while redefining which categories are proposed in new forms.
- Replace the current `Diagnostic final` field with a `Transmission clinique` text block limited to 300 words, with helper text explaining the expected content.
- Add a second text block, `Reflexion sur le cas`, also limited to 300 words, with helper text focused on personal clinical progression.
- Add a chapo explaining the goal of the casebook and clearly separating the clinical transmission from the personal reflection.
- Present one example model for `Transmission clinique` and one for `Reflexion sur le cas`.
- Replace the current 3-screen workflow with a single vertically scrollable page.
- Group the end-of-entry actions at the bottom of the page with the buttons `Enregistrer le brouillon`, `Annuler`, and `Valider`.
- Preserve the readability of old categories and old fields on historical entries.
- Add a category activation mechanism, for example through an `active` field, so only active groupings appear in new forms.

## Capabilities

### New Capabilities
- `caselog-page`: Defines the content, structure, backward-compatibility, and input requirements of the Caselog page on a single screen.

### Modified Capabilities

## Impact

- OpenSpec artifacts describing the expected Caselog page behavior.
- Tables `competvet_case_cat` and `competvet_case_field`, which drive the dynamic Caselog structure.
- Form and validation logic in the `competvet` module for clinical case entry.
- Templates, styles, and page organization for the Caselog interface.
- Any draft-save, cancel, and validation handling tied to the unified page.
- Rendering of historical entries so inactive categories and fields remain visible.
