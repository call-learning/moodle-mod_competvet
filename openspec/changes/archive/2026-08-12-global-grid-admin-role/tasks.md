## 1. Permission model

- [x] 1.1 Add a dedicated system-level capability for global CompetVet grid administration with appropriate default archetypes
- [x] 1.2 Decide and document the intended role-assignment path for institutions that want a restricted global-grid administrator profile
- [x] 1.3 Reconcile the existing `editcriteria` permission model so global grid administration no longer depends on a mismatched module-context capability

## 2. Access control alignment

- [x] 2.1 Update the global grid management page access check to use the dedicated global-grid administration capability
- [x] 2.2 Update grid and criterion management APIs to enforce the same authorization rule for reading, updating, and deleting global grids and criteria
- [x] 2.3 Update grid and criterion permission helpers so edit and delete eligibility checks remain consistent with the new global authorization model

## 3. Validation and coverage

- [x] 3.1 Verify that an authorized restricted user can access the global grid settings or management page and edit global grids
- [x] 3.2 Verify that an unauthorized user is denied consistently across the page and API operations
- [x] 3.3 Add or update automated coverage for the new capability and the restricted global-grid administration flow
