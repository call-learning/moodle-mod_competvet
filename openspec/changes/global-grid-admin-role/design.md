## Context

See `proposal.md` for motivation. The current global grid editing flow already spans system-level operations: `manage_criteria` validates `context_system::instance()`, and grid or criterion delete checks also use `context_system::instance()`. However, the capability currently used for editing criteria is declared as `mod/competvet:editcriteria` with `CONTEXT_MODULE`, and the page access check in `managecriteria` uses the current page context rather than a clearly defined global-grid permission model. This creates an inconsistent authorization story for a feature that is effectively global.

## Goals / Non-Goals

**Goals:**
- Introduce a clear, restricted authorization path for editing global CompetVet grids.
- Align page access, API access, and delete checks on the same authorization model.
- Allow institutions to assign this ability through a dedicated capability and, if desired, a role based on it.
- Avoid forcing full manager access for users who only need to maintain global grids.

**Non-Goals:**
- Redesign the full criteria management UI.
- Change module-scoped permissions unrelated to global grid administration.
- Broaden this permission to every CompetVet administrative feature.

## Decisions

### Use a dedicated system-level capability for global grid administration
Decision: introduce a dedicated system-context capability for global grid administration, and treat roles as an assignment mechanism built on top of that capability rather than as the primary technical primitive.

Rationale: the runtime flow is already effectively global, so a system-level capability is a better fit than reusing a module-context permission. It also gives administrators a clean way to create or assign a restricted role without hardcoding role behavior in the module.

Alternatives considered:
- Reuse `mod/competvet:editcriteria` as-is. Rejected because it is currently declared at module context while several checks already operate at system context.
- Implement a hardcoded custom role only. Rejected because Moodle permissions should remain capability-driven, with roles as configuration.

### Align all authorization checks to the same capability model
Decision: use the dedicated global-grid capability consistently for page access, webservice update access, and grid or criterion delete eligibility checks.

Rationale: the current mixture of page-context checks and system-context checks is hard to reason about and can produce authorization gaps or false denials. A single rule reduces ambiguity.

Alternatives considered:
- Keep separate rules for page display and backend operations. Rejected because it risks UI/API drift.

### Keep the permission restricted to global grids
Decision: scope the new authorization to global grid and criterion management only, without implicitly granting unrelated planning, grading, or observation administration rights.

Rationale: the user request is explicitly about a restricted way to edit global grids from the settings page.

Alternatives considered:
- Expand the capability to all settings pages. Rejected because it exceeds the requested scope.

## Risks / Trade-offs

- [Permission migration ambiguity] Existing users with `editcriteria` may rely on the current behavior in ways that are not fully documented -> Mitigation: define clear defaults for managers and editing teachers, and verify access expectations during rollout.
- [Context mismatch regressions] Some code paths may continue checking the old capability or the wrong context -> Mitigation: audit page, webservice, and persistent-level permission checks together.
- [Role expectation drift] Administrators may expect a turnkey role instead of a capability -> Mitigation: document the capability clearly and decide whether setup should create or update a recommended role assignment path.

## Migration Plan

1. Add a dedicated system-level capability for global grid administration, with appropriate default archetypes.
2. Update page access and API access checks for global grid management to rely on that capability.
3. Update grid and criterion delete or edit eligibility checks to use the same capability model.
4. Verify that authorized restricted users can access the global grid settings page, while unauthorized users are denied consistently.
5. Document or provide the intended role-assignment path for institutions that want a restricted global-grid administrator profile.
