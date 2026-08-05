## 1. Final grade cleanup flow

- [ ] 1.1 Confirm the final grade update path still queues the post-grade workflow with the student and planning context needed for TODO cleanup
- [ ] 1.2 Align the `student_graded` post-grade flow with the explicit rule that pending TODOs for the graded student and planning must no longer remain pending after a successful final grade update
- [ ] 1.3 Ensure the cleanup scope does not affect TODOs for other students or other plannings

## 2. Safety and coverage

- [ ] 2.1 Add or update automated coverage for successful final grade assignment clearing the graded student's pending TODOs for the planning
- [ ] 2.2 Add or update automated coverage proving unrelated TODOs remain untouched for other students or other plannings
- [ ] 2.3 Verify the final grade notification flow remains coherent after the TODO cleanup behavior is enforced
