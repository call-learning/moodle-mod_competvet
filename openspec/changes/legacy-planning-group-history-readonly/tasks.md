## 1. Audit group-dependent planning behavior

- [ ] 1.1 Review where planning labels, memberships, and editability currently depend on a live Moodle group across APIs, views, and reports
- [ ] 1.2 Identify which planning-linked entities will be used to derive the readonly historical student list when no live group membership is available
- [ ] 1.3 Identify the code paths that must switch from direct `groups_*` reads to live-group-or-fallback resolution
- [ ] 1.4 Verify that the orphan-user repair behavior from commit `8ea3fd3e8b0be33224c308ca6331dd81fa68af6e` is integrated on the target main branch, or import its equivalent first
- [ ] 1.5 Validate the specific A-to-B student move scenario so existing data on group A is handled by orphan-user repair rather than by historical readonly logic

## 2. Introduce degraded historical planning behavior

- [ ] 2.1 Add detection for plannings whose referenced live group no longer exists
- [ ] 2.2 Add shared logic to resolve a planning's display group label from the live group or fallback `Groupe inconnu (<groupid>)`
- [ ] 2.3 Add shared logic to derive readonly planning students from attached CompetVet records when the group is missing

## 3. Apply readonly planning behavior

- [ ] 3.1 Prevent editing flows from modifying plannings that are historical because their group is missing
- [ ] 3.2 Preserve the orphan-user repair workflow for plannings whose live group still exists, and branch to readonly historical behavior only when the group is actually missing
- [ ] 3.3 Add readonly planning display logic that still exposes attached observations, evaluations, certifications, and the derived student list
- [ ] 3.4 Update report or list surfaces that show planning group information so missing-group plannings remain understandable there

## 4. Preserve behavior across backup and restore

- [ ] 4.1 Extend restore-facing planning resolution so plannings with missing groups keep coherent fallback labels and readonly semantics after restore
- [ ] 4.2 Verify backup/restore does not reintroduce direct live-group assumptions for missing-group plannings

## 5. Protect with tests

- [ ] 5.1 Add or update automated coverage for a planning whose live group still exists
- [ ] 5.2 Add or update automated coverage for a planning whose group still exists but uses the orphan-user repair path
- [ ] 5.3 Add or update automated coverage for a student moved from group A to group B after entering data in group A
- [ ] 5.4 Add or update automated coverage for a planning whose group was deleted and therefore uses readonly mode with the fallback label
- [ ] 5.5 Add or update automated coverage for historical student derivation on a missing-group planning
- [ ] 5.6 Add or update automated coverage for backup/restore of historical plannings
