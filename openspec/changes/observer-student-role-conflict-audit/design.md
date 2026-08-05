## Context

See `proposal.md` for motivation. CompetVet currently derives visible roles through code paths that include inherited roles from parent contexts. In particular, `situation::get_filtered_roles()` calls `get_user_roles()` with parent-context lookup enabled, which means a user assigned `observer` directly on the module can still appear to hold `student` through a course-level assignment. That makes the effective-role interpretation ambiguous if the module does not explicitly prioritize direct module assignments. In parallel, the CSV role importer already removes CompetVet-managed module-context roles before reassigning them, which suggests the intended behavior is to keep the module context authoritative for CompetVet-specific role state. The user also wants a separate audit of what happens when a planning group disappears, but that needs to remain distinct from role-resolution bugs.

## Goals / Non-Goals

**Goals:**
- Make direct CompetVet activity role assignments win over inherited parent-context roles for effective CompetVet role resolution.
- Confirm that the relevant conflict to audit is student versus observer, not student versus teaching roles.
- Keep CSV role import aligned with the same effective-role model so stale module-level assignments do not accumulate.
- Audit missing-group behavior separately so it is not mistaken for a role conflict.

**Non-Goals:**
- Redesign Moodle's global role inheritance model.
- Change unrelated grading or planning behavior beyond what is needed for the audit.
- Merge missing-group planning remediation into the role-resolution fix itself.

## Decisions

### Treat direct module role assignments as authoritative for CompetVet role resolution
Decision: when CompetVet computes a user's effective role for a module, direct assignments on that module take precedence over inherited roles from course, category, or system contexts.

Rationale: the reported bug is consistent with inherited `student` leaking into CompetVet's role resolution even when the user was explicitly made `observer` on the module. The module is the narrowest and most intentional context for CompetVet-specific role state, so it should win.

Alternatives considered:
- Keep inherited and direct roles at equal weight. Rejected because that preserves the ambiguity that triggered the false conflict.

### Audit student-versus-observer conflicts before teacher overlaps
Decision: frame the audit primarily around student/observer collisions and only treat teaching-role overlaps as secondary context unless evidence shows otherwise.

Rationale: the user explicitly suspects the conflict is not student versus teacher. The role importer already manages `student`, `teacher`, and `editingteacher` at module level, but the concrete false-conflict scenario described maps more directly to inherited student plus direct observer.

Alternatives considered:
- Generalize immediately to every role overlap. Rejected because it broadens the change before confirming the real failing path.

### Keep CSV import behavior aligned with the effective-role rule
Decision: preserve and validate the importer's cleanup-first behavior so module-context CompetVet roles remain the current source of truth after each import.

Rationale: if direct module assignments are authoritative, CSV import must not leave stale module-role combinations in place. The existing importer already unassigns managed module roles first, so the change should explicitly rely on and test that behavior.

Alternatives considered:
- Leave import behavior out of scope. Rejected because stale imports can recreate the same ambiguity after the resolution fix.

### Keep missing-group analysis separate from role analysis
Decision: audit missing-group behavior in parallel, but do not fold it into the role-conflict resolution path.

Rationale: a planning whose group disappeared is a data-availability problem, not necessarily a role-resolution problem. Conflating the two would make debugging and testing much less clear.

Alternatives considered:
- Treat every visibility problem as a role bug first. Rejected because it hides a separate class of planning issues.

## Risks / Trade-offs

- [Hidden parent-context dependency] Some current behaviors may rely on inherited roles remaining visible in raw role lists -> Mitigation: scope the precedence change to effective CompetVet role resolution rather than to every raw role inspection path.
- [Import mismatch] CSV import and runtime resolution could diverge if they do not use the same assumptions about module authority -> Mitigation: add regression coverage that exercises both imported assignments and runtime role reads.
- [False separation] A real-world issue could combine missing groups and role conflicts in the same symptom report -> Mitigation: audit and test both paths explicitly, but keep their decision logic separate.

## Migration Plan

1. Audit the current effective-role resolution path, especially calls that include parent contexts.
2. Confirm the target conflict path with tests or fixtures covering inherited student and direct observer combinations.
3. Adjust or document effective-role precedence so direct module assignments win for CompetVet role decisions.
4. Validate that CSV role import continues to clear stale module-context CompetVet roles before reassignment.
5. Audit missing-group planning behavior separately and verify it is not confused with role conflict handling.
6. Add regression coverage for inherited-role conflicts, CSV reimports, and missing-group separation.
