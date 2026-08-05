## 1. Structural cleanup and migration

- [ ] 1.1 Add an `active` field to `competvet_case_cat` with an upgrade path and explicit defaults for existing categories
- [ ] 1.2 Define which existing Caselog categories remain active for current forms and which become legacy-only after the cleanup
- [ ] 1.3 Review the current Caselog field set and keep the runtime structure anchored on `competvet_case_field` while preserving legacy category and field definitions needed for historical readability
- [ ] 1.4 Fix the Caselog structure cache usage so reads and writes use consistent keys and the cache can be invalidated when categories or fields change

## 2. Runtime structure resolution

- [ ] 2.1 Update Caselog structure resolution so current create and edit forms only use active categories
- [ ] 2.2 Refactor historical Caselog rendering so it can display values linked to inactive legacy categories and fields without depending only on the current active structure
- [ ] 2.3 Audit whether any current edit path must preserve legacy values on existing entries while still enforcing the cleaned structure for ongoing form usage
- [ ] 2.4 Remove or isolate current runtime assumptions that depend on legacy field idnumbers such as `motif_presentation` for Caselog labels or summaries

## 3. Downstream safety and prerequisite coverage

- [ ] 3.1 Audit ancillary flows such as backup and restore to determine whether category activation state must be serialized or restored consistently, and fix any schema mismatches around `competvet_case_fields`
- [ ] 3.2 Review reportbuilder and other derived Caselog consumers so they remain coherent when categories become inactive or legacy-only
- [ ] 3.3 Add or update automated coverage for active versus inactive category selection, historical readability of legacy Caselog entries, and cache invalidation behavior
- [ ] 3.4 Document or verify this change as the structural prerequisite for the downstream `change-page-caselog` work
