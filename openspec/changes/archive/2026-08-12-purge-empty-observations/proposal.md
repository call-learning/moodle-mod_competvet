## Why

Evaluation observations can be created by mistake or remain empty after testing or abandoned input. As long as they remain present and are counted as real observations, they pollute indicators, distort pedagogical interpretation, and complicate data maintenance.

## What Changes

- Define clearly what counts as an empty observation in CompetVet, especially an observation with no usable grade.
- Add a purge process that removes observations created by mistake and left empty, together with their dependent data.
- Ensure empty observations are no longer counted as real observations in indicators and views that rely on those counters.
- Keep newly created or unfinished empty observations available in observation views until an explicit purge removes them.
- Align effective-count APIs and planning data checks so they ignore empty observations, while observation visibility APIs continue to expose unfinished empty observations until purge.
- Add regression coverage for identifying empty observations, purging them, and excluding them from counters.

## Capabilities

### New Capabilities
- `empty-observation-lifecycle`: Handles identification, counter exclusion, and purge of empty or mistakenly created evaluation observations.

### Modified Capabilities

## Impact

- Local observation API and observation-read logic per student.
- External APIs and views that expose `numberofobservations`, `hasanyobservations`, or equivalent indicators.
- Maintenance or administrative process used to purge existing empty observations.
- Cascading deletion of comments and levels linked to purged observations.
- PHPUnit coverage for purge, counting, and historical-data compatibility.
