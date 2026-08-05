## 1. Audit current user-linked data and read paths

- [ ] 1.1 Inventory the CompetVet tables and APIs that store or expose user-linked data for students, observers, supervisors, recipients, and authors
- [ ] 1.2 Review current active student listing logic to identify where deleted Moodle users already disappear and where stale references can remain
- [ ] 1.3 Review every major `get_user_info()` consumer to identify which historical surfaces must tolerate deleted users safely
- [ ] 1.4 Review the current privacy integration gap, including metadata strings and missing provider implementation

## 2. Specify and implement deleted-user handling

- [ ] 2.1 Introduce or formalize a shared deleted-user fallback representation for historical CompetVet reads
- [ ] 2.2 Ensure active planning student lists exclude deleted users while historical observations and related records remain readable
- [ ] 2.3 Align representative observation, certification, todo, notification, and planning views with the deleted-user fallback behavior
- [ ] 2.4 Keep this implementation decoupled from the separate group-deletion specification

## 3. Implement privacy lifecycle support

- [ ] 3.1 Define the per-record privacy policy for observations, certifications, case entries, todos, notifications, and related user-linked data
- [ ] 3.2 Implement or update the Moodle privacy provider so CompetVet metadata accurately reflects stored personal data
- [ ] 3.3 Implement privacy export for the CompetVet data covered by the declared policy
- [ ] 3.4 Implement privacy erasure or anonymization behavior according to the documented policy while preserving plugin integrity

## 4. Protect behavior with automated tests

- [ ] 4.1 Add regression coverage for the workflow: create a situation, evaluate a student, delete the student, verify active student listings remain coherent
- [ ] 4.2 Add regression coverage proving historical CompetVet records remain readable with deleted-user fallback semantics
- [ ] 4.3 Add regression coverage for privacy metadata so the plugin no longer declares false absence of personal data
- [ ] 4.4 Add regression coverage for privacy export and erasure behavior on representative CompetVet records
