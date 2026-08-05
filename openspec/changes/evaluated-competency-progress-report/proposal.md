## Why

Today, CompetVet exposes evaluations observation by observation, but it does not provide a consolidated view of the competencies already evaluated, acquired, not acquired, or still missing for a student. That lack of synthesis limits pedagogical steering, student follow-up, and observer support when choosing what to evaluate next.

## What Changes

- Add a competency progression report that consolidates, per student, competencies already evaluated, acquired, not acquired, and still to be evaluated.
- Make that report usable by the teaching team to objectify evaluation coverage and identify gaps.
- Provide an adapted student view so learners can track progression on competencies already observed or still expected.
- Expose the same synthesis during observation so an observer can quickly see competencies already covered, missing, or not acquired before entering a new evaluation.
- Constrain the consolidation so it remains coherent with active grids and criteria on situations without breaking the display of existing historical data.
- Add regression tests for status consolidation and for access rights on the different views.

## Capabilities

### New Capabilities
- `competency-progress-report`: Provides a consolidated per-student view of evaluated, acquired, not acquired, and pending competencies that can be reused in reports, student follow-up, and observation screens.

### Modified Capabilities

## Impact

- Local and external APIs that currently aggregate evaluations and criteria per planning or per student.
- Evaluation and observation viewing surfaces for teachers, observers, and students.
- `reportbuilder` infrastructure to expose a usable and filterable synthesis.
- Access-control rules so a student cannot see anything other than their own progression.
- PHPUnit coverage for aggregation, consolidated statuses, historical compatibility, and role-based visibility.
