## 1. Audit grade-item dependencies

- [x] 1.1 Review every CompetVet path that reads the module grade item or its grade type
- [x] 1.2 Review every CompetVet path that creates or updates the module grade item in the gradebook
- [x] 1.3 Identify where an incompatible non-points grade-item configuration can survive or reappear

## 2. Enforce points-compatible grade configuration

- [x] 2.1 Add or tighten enforcement so the CompetVet grade item stays in a points-compatible mode whenever numeric grading is required
- [x] 2.2 Ensure the enforcement logic is applied consistently to grade-item creation and later grade synchronization
- [x] 2.3 Add guardrails so incompatible grade-item states are corrected or explicitly rejected instead of being used silently

## 3. Keep read paths coherent

- [x] 3.1 Align grade-type reads and related grade metadata access with the enforced points-based configuration
- [x] 3.2 Verify downstream note displays and calculations continue to receive coherent numeric grade-item information

## 4. Protect with tests

- [x] 4.1 Add or update automated coverage for creation of a CompetVet grade item in points mode
- [x] 4.2 Add or update automated coverage for normalization or rejection of an incompatible grade-item type
- [x] 4.3 Add or update automated coverage for grade reads and grade writes using the same enforced points-compatible configuration
