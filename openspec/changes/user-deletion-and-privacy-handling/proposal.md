## Why

Today, CompetVet handles many data records keyed by user IDs stored in its own tables, but the expected behavior after a user is deleted is not explicit. Without a clear rule, the plugin risks both application-display inconsistencies and historical data that is hard to interpret, along with inadequate privacy integration for deletion or export requests.

## What Changes

- Specify the functional behavior of CompetVet when a Moodle user linked to observations, certifications, cases, or todos is deleted.
- Ensure student lists and application views remain usable after a student is deleted, without breaking the display of historical data that is still present.
- Define whether historical data should be preserved, anonymized, hidden, or deleted depending on its type and the deletion context.
- Clarify integration with Moodle's privacy API for personal-data export and erasure on request.
- Add regression coverage for the minimal requested scenario: create a situation, evaluate a student, delete the student, and verify the application and student lists.
- Keep group handling out of this change, since it is already covered by another spec.

## Capabilities

### New Capabilities
- `deleted-user-data-handling`: Defines CompetVet behavior when a user referenced by module data no longer exists or has been deleted, both for application display and for preservation of historical traces.
- `competvet-privacy-lifecycle`: Defines export and erasure of CompetVet personal data through Moodle's privacy API.

### Modified Capabilities

## Impact

- Local and external APIs that rebuild `userinfo` payloads from stored IDs.
- Student lists, observation views, certifications, notifications, todos, and planning screens.
- Rules for a student's presence in a group or list after Moodle deletion.
- Plugin privacy integration, including metadata declaration and export or erasure operations.
- PHPUnit and, if needed, Behat coverage for user deletion and historical data handling.
