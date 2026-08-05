## Why

When a Moodle group attached to a planning is deleted, CompetVet loses part of the context needed to understand and use historical situations. The planning continues to reference a `groupid` that is no longer usable, which degrades display, editing, and the historical readability of pedagogical data.
This work must also stay coherent with the `Fix orphan user` functionality developed in commit `8ea3fd3e8b0be33224c308ca6331dd81fa68af6e`, which addresses a different kind of mismatch between a planning and the members of its group.

## What Changes

- Switch a planning to read-only mode when its group no longer exists, instead of continuing to offer incomplete or incoherent editing.
- Define how to rebuild a displayable student list from entities already attached to the planning when the group has disappeared.
- Show the live group name while it exists, otherwise show a fallback label such as `Groupe inconnu (<groupid>)`.
- Distinguish this case explicitly from `Fix orphan user`: if the group still exists, stay in the orphan-user repair flow; if the group itself has disappeared, switch to historical read-only mode.
- Account for this behavior in backup and restore and in the associated regression tests.

## Capabilities

### New Capabilities
- `legacy-planning-history`: Handles preservation and read-only display of historical plannings whose Moodle group has been deleted.

### Modified Capabilities

## Impact

- Planning retrieval and display logic when the linked Moodle group has disappeared.
- Sequencing dependency: `Fix orphan user` should first be integrated into the main branch so it can define the baseline behavior for plannings whose group still exists.
- Views, editing flows, and reports that display the group name or planning details.
- Backup and restore of plannings and their group context.
- PHPUnit and, if needed, Behat coverage for historical plannings.
