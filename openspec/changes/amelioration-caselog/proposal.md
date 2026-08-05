## Why

The current Caselog structure does not allow categories and input fields to be cleaned up without risking breakage when reading historical case entries. This change is needed now to clean up the Caselog structure and introduce a backward-compatible foundation that the downstream `change-page-caselog` change can rely on.

## What Changes

- Clean up the Caselog structure so currently useful categories and fields are clearly separated from legacy elements that only need to remain readable.
- Keep `competvet_case_cat` as the structure used to group fields page by page, and `competvet_case_field` as the runtime definition of the fields attached to each category.
- Add an activation flag on categories to control which ones remain available in current forms after the cleanup.
- Ensure old categories and old fields remain visible on existing Caselog entries even when they are no longer proposed in current forms.
- Adapt Caselog structure resolution so it distinguishes current create or edit usage from historical read-only usage.
- Establish this change explicitly as a structural prerequisite for `change-page-caselog`, without implementing that page's functional redesign here.

## Capabilities

### New Capabilities
- `caselog-structure`: Defines how the dynamic Caselog structure is cleaned up and prepared for future changes, how active categories are selected for current forms, and how historical data remains readable.

### Modified Capabilities

## Impact

- Tables `competvet_case_cat` and `competvet_case_field`, which drive the runtime Caselog structure.
- Related technical flows such as backup and restore if category activation state must remain coherent there.
- APIs and rendering layers that build Caselog forms and display Caselog entries.
- Data or state migrations needed to mark categories as active or legacy.
- Upstream dependency for `change-page-caselog` and future functional evolutions of the Caselog interface.
