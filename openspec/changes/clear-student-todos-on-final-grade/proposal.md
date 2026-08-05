## Why

When a student receives a final grade, they should no longer keep TODOs that are still tied to that evaluation in the same pedagogical context. Formalizing this behavior now avoids inconsistencies between grading state and the task list, and clarifies logic that already exists partially in the code.

## What Changes

- Define the business rule explicitly for removing or closing TODOs associated with a student when a final grade is recorded for that student.
- Tie that cleanup to the final-grade update flow so it happens reliably for the correct student and planning.
- Clarify the scope of affected TODOs, especially their link to the target student and the graded planning.
- Add test coverage to verify that recording a final grade leaves the TODO list in a coherent state.

## Capabilities

### New Capabilities
- `student-grade-todo-cleanup`: Defines how TODOs associated with a student are cleaned up when a final grade is assigned.

### Modified Capabilities

## Impact

- Final-grade update flow in the `competvet` module.
- Task or service responsible for notifications and post-grade cleanup.
- TODO API and persistence linked to students and plannings.
- Grading and TODO-management tests.
