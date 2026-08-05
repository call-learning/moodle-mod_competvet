## 1. Audit current backup/restore mapping behavior

- [ ] 1.1 Review how restore currently matches or creates grids and criteria, including the existing `idnumber`-based reuse behavior
- [ ] 1.2 Identify which grid-backed record families must remain consistent after restore, including observations and certifications
- [ ] 1.3 Define the intended reuse-versus-duplication policy for grids and criteria during restore

## 2. Harden restore integrity rules

- [ ] 2.1 Update restore logic so grid reuse or creation follows the intended explicit policy
- [ ] 2.2 Update criterion restore logic so reused grids do not accumulate duplicate criteria and parent-child hierarchies remain coherent
- [ ] 2.3 Verify that restored situations and downstream records point to the effective remapped grids and criteria
- [ ] 2.4 Verify that restored certification declarations and validations align with the intended restored certification criteria

## 3. Expand backup/restore regression coverage

- [ ] 3.1 Extend the existing backup/restore tests to assert grid and criterion integrity, not only record-count parity
- [ ] 3.2 Add a regression scenario for restore into a fresh target asserting grid-backed observations and certifications
- [ ] 3.3 Add a regression scenario for repeated restore of the same backup, verifying grid and criterion structure remains consistent
- [ ] 3.4 Add assertions that fail on unintended duplication of grids or criteria
