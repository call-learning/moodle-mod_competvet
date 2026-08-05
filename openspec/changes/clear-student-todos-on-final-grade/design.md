## Context

See `proposal.md` for motivation. The current final grade write path runs through `classes/external/manage_grade.php`, which updates the Moodle grade item and queues the adhoc task `mod_competvet\task\student_graded`. That task already deletes pending TODOs for the graded student and planning before sending the student notification email. The requested change is therefore less about inventing new behavior than about formalizing, hardening, and testing the existing implicit rule so it remains reliable and correctly scoped.

## Goals / Non-Goals

**Goals:**
- Make TODO cleanup on final grade assignment an explicit supported behavior.
- Keep the cleanup correctly scoped to the graded student and planning.
- Preserve coherence between final grade updates, pending TODO state, and post-grade notification behavior.
- Add coverage so the behavior does not regress silently.

**Non-Goals:**
- Redesign the entire TODO lifecycle system.
- Change unrelated TODO actions or statuses outside the final grade flow.
- Broaden cleanup to every TODO in the course regardless of planning or target user.

## Decisions

### Reuse the existing post-grade task as the primary cleanup point
Decision: keep the `student_graded` post-grade flow as the primary place where pending TODOs are cleared after a successful final grade update.

Rationale: the grade write path already queues this task, and the task already has the student, module, and planning context required to scope cleanup correctly. Reusing it minimizes architectural churn and keeps notification plus cleanup behavior in one place.

Alternatives considered:
- Delete TODOs directly inside `manage_grade::update()`. Rejected because it duplicates post-grade logic and mixes grade persistence with secondary side effects.
- Introduce a second dedicated cleanup task. Rejected because it adds orchestration without clear benefit for this scope.

### Keep cleanup scoped to pending todos for the graded planning and target student
Decision: define the cleanup scope as pending TODOs whose `targetuserid` and `planningid` match the final grade context.

Rationale: this matches the current implementation and avoids deleting unrelated TODOs that may still be actionable in other plannings or for other students.

Alternatives considered:
- Remove every TODO for the student in the module. Rejected because the user request can be satisfied with planning-scoped cleanup and broader deletion would risk data loss.

### Treat the current implementation as behavior to verify, not as proof of correctness
Decision: explicitly test the cleanup behavior around final grade assignment, including scoping and successful grade updates.

Rationale: the behavior already exists informally in the task, but without direct tests for this exact business rule it can regress during later grading or notification changes.

Alternatives considered:
- Leave the current code undocumented because it already works. Rejected because the point of this change is to make the rule explicit and reliable.

## Risks / Trade-offs

- [Async timing] Cleanup currently happens in an adhoc task, so TODO removal may not be visible until the task runs -> Mitigation: document the cleanup point clearly and cover task execution in tests.
- [Over-deletion] A too-broad cleanup query could remove TODOs for the wrong student or planning -> Mitigation: keep cleanup keyed on `targetuserid`, `planningid`, and pending status.
- [Silent regression] Future edits to grade notification flow could accidentally remove the cleanup side effect -> Mitigation: add focused tests around final grade assignment and the `student_graded` task.

## Migration Plan

1. Formalize the business rule in tests and planning artifacts.
2. Confirm the final grade update path still queues the post-grade task with student and planning context.
3. Adjust the cleanup logic only if needed to match the explicit scope for pending TODOs.
4. Verify the final grade flow still sends notifications while leaving no pending TODOs for the graded student and planning.
