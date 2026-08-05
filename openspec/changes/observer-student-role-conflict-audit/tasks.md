## 1. Audit the current conflict path

- [ ] 1.1 Review the current effective-role resolution paths that include inherited parent-context roles
- [ ] 1.2 Confirm with code and tests that the target conflict is student versus observer rather than student versus teaching roles
- [ ] 1.3 Identify the affected APIs, views, and helper methods that currently rely on ambiguous role resolution

## 2. Align effective role resolution

- [ ] 2.1 Update or centralize CompetVet effective-role resolution so direct module assignments take precedence over inherited parent-context roles
- [ ] 2.2 Ensure the resolved role logic distinguishes raw inherited-role visibility from the effective CompetVet role used by the module
- [ ] 2.3 Verify that role-conflict handling still behaves coherently for users who only have inherited roles and no direct module assignment

## 3. Keep CSV role import coherent

- [ ] 3.1 Verify that CSV role import clears stale CompetVet-managed module-context roles before reassignment
- [ ] 3.2 Tighten or document the importer behavior if needed so repeated imports cannot leave obsolete student plus observer combinations on the same module context

## 4. Separate missing-group analysis

- [ ] 4.1 Audit what happens when a planning group disappears and document the current behavior separately from role conflict handling
- [ ] 4.2 Verify that missing-group visibility issues are not being misdiagnosed as role-resolution conflicts

## 5. Protect with tests

- [ ] 5.1 Add or update automated coverage for a user who is student in a parent context and observer directly on the CompetVet activity
- [ ] 5.2 Add or update automated coverage for CSV role reimport so stale module-context role combinations are removed
- [ ] 5.3 Add or update automated coverage confirming that teacher-role overlap is not the primary failing path in the audited conflict
- [ ] 5.4 Add or update automated coverage or audit checks for the separate missing-group scenario
