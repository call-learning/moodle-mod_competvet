## 1. Audit current grid binding and dependencies

- [ ] 1.1 Review how `evalgrid`, `certifgrid`, and related grid fields are stored on situations and resolved by evaluation and certification workflows
- [ ] 1.2 Inventory the activity-scoped data families that make an evaluation-grid or certification-grid override unsafe once data exists
- [ ] 1.3 Identify the most appropriate activity-settings surface for exposing a grid override action

## 2. Define and implement safe override behavior

- [ ] 2.1 Introduce the activity-level override action for evaluation and certification grids in the CompetVet settings flow
- [ ] 2.2 Implement workflow-specific safety checks so overrides are allowed only when no dependent data exists for the targeted workflow
- [ ] 2.3 Return clear blocking messages when an override is refused because existing data would be affected
- [ ] 2.4 Ensure successful overrides update subsequent grid resolution and criteria reads for the activity

## 3. Keep destructive behavior explicit

- [ ] 3.1 Ensure the standard override path never silently deletes dependent evaluation or certification data
- [ ] 3.2 If a destructive reset path is kept in scope, separate it clearly from the safe override action with stronger confirmation and scoped cleanup rules

## 4. Protect the behavior with tests

- [ ] 4.1 Add regression coverage for replacing a wrongly selected grid before any dependent data exists
- [ ] 4.2 Add regression coverage for a blocked override once evaluation data exists
- [ ] 4.3 Add regression coverage for a blocked override once certification-dependent data exists
- [ ] 4.4 Add regression coverage proving the newly selected grid is used after a successful safe override
