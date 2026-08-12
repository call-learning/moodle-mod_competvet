## 1. Audit current data and rules

- [x] 1.1 Review the existing evaluation, observation, and criterion APIs to identify the source data that can feed consolidated competency progression
- [x] 1.2 Identify the current server-side rule, or define the missing one, that distinguishes acquired from evaluated-but-not-acquired for a competency
- [x] 1.3 Review existing access-control paths for students, observers, and staff to determine the correct visibility scope for progression data
- [x] 1.4 Identify the existing reportbuilder and UI entry points where the progression summary should be exposed first

## 2. Define and implement the shared progression model

- [x] 2.1 Introduce a shared aggregation service or API that consolidates competency progression per student from existing evaluations
- [x] 2.2 Normalize progression states so the output can distinguish at least not evaluated, evaluated not acquired, and acquired
- [x] 2.3 Ensure repeated observations update consolidated progression without losing historical evidence
- [x] 2.4 Ensure legacy evaluation data still contributes to progression output without requiring destructive migration

## 3. Expose the progression in the main consumer surfaces

- [x] 3.1 Add a staff-facing report surface for competency progression using the shared aggregation logic
- [x] 3.2 Add or adapt a student-facing view so a student can consult personal competency progression
- [x] 3.3 Add or adapt an observer-facing observation surface so missing or non-acquired competencies are visible during observation
- [x] 3.4 Align output labels and filtering options across report, student, and observer surfaces while keeping the same underlying status rules

## 4. Protect behavior with automated tests

- [x] 4.1 Add regression coverage for the progression aggregation rules across missing, non-acquired, and acquired competencies
- [x] 4.2 Add regression coverage for repeated evaluations on the same competency and for historical evaluation compatibility
- [x] 4.3 Add regression coverage for access control so students only see their own progression while staff and observers keep expected visibility
- [x] 4.4 Add regression coverage for the report or exported progression output so missing competencies remain identifiable

## 5. Remaining work

- [ ] 5.1 Troubleshoot the competency progression report: fix duplicate rows, ensure pagination works, and verify data aggregation is correct (distinct, proper entity joins, URL parameters)
