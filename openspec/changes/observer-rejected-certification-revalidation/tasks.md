## 1. Audit and define workflow rules

- [ ] 1.1 Review the current certification aggregation logic in `certifications` to identify exactly how rejection, waiting, and validated states are currently derived
- [ ] 1.2 Define the effective workflow rules for observer rejection, pending revalidation, and final validated state within one certification declaration lifecycle
- [ ] 1.3 Review the validation-todo lifecycle in observers and `todos` to identify where rejection currently closes work too early
- [ ] 1.4 Identify the report and client-facing certification outputs that depend on the same status derivation, including the mobile-consumed paths

## 2. Correct certification state handling

- [ ] 2.1 Update certification state aggregation so a rejection outcome cannot be exposed as a final validated certification
- [ ] 2.2 Ensure a rejected certification returns to a pending validation state that can be reviewed again
- [ ] 2.3 Adjust validation completion handling so todos are preserved or reopened when the validation cycle remains incomplete
- [ ] 2.4 Verify that report-facing and client-facing certification outputs reuse the corrected server-side status behavior

## 3. Protect the workflow with tests

- [ ] 3.1 Add or update automated coverage for a successful observer validation flow that should still complete normally
- [ ] 3.2 Add or update automated coverage for an observer rejection flow that must leave the certification pending and actionable
- [ ] 3.3 Add or update automated coverage for a reject-then-revalidate flow that must later expose the certification as validated
- [ ] 3.4 Add or update verification for report-visible certification states so rejected certifications do not appear validated there

## 4. Release coordination

- [ ] 4.1 Add a changelog note that the certification validation workflow changed and that the mobile application must be reviewed and, if needed, adapted or revalidated
