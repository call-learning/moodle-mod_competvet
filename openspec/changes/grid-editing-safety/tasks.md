## 1. Audit the risky editing paths

- [ ] 1.1 Review the current grid editing flow across `manage_criteria` and the `criteria` API to identify mutation paths that can still corrupt the model
- [ ] 1.2 Reconstruct or approximate the historically dangerous evaluation-grid editing sequence and capture the structural invariants it must preserve
- [ ] 1.3 Identify which current operations need stronger protection around updates, deletes, sort-order changes, option changes, or parent-child structure changes
- [ ] 1.4 Audit the backup/restore flow for `competvet_grid`, `competvet_criterion`, and their mappings into situations, observations, and certifications to identify restore-time corruption risks

## 2. Harden structural safety

- [ ] 2.1 Add or tighten guards so unsupported or dangerous grid mutations cannot leave the model in an inconsistent state
- [ ] 2.2 Preserve or improve existing protections for criteria and options already used by situations, observations, or related records
- [ ] 2.3 Verify that edited grids remain readable and coherent for later retrieval and grading flows after supported mutations
- [ ] 2.4 Tighten backup/restore assumptions or mappings if restore can reconnect restored data to an incoherent grid or criterion structure

## 3. Regression protection

- [ ] 3.1 Add or update automated coverage for risky evaluation-grid editing scenarios, including multi-step edit sequences
- [ ] 3.2 Add or update coverage for safe update, delete, option-edit, and sort-order flows that must remain supported
- [ ] 3.3 Add or update backup/restore round-trip coverage so restored grids, criteria, and dependent records remain coherent, including reuse-by-`idnumber` cases
- [ ] 3.4 Verify that the hardened behavior prevents recurrence of model-breaking edits without regressing legitimate grid administration or restore flows
