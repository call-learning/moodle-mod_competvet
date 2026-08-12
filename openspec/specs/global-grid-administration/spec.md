# global-grid-administration Specification

## Purpose

Define how a user can receive restricted access and edit rights for global CompetVet grids without also receiving the module's broader administration permissions.

## Requirements

### Requirement: Restricted users can access global grid administration
The system SHALL provide a dedicated authorization mechanism for accessing global CompetVet grid administration.
The system SHALL allow a user holding that authorization to access the page used to manage global grids and criteria.

#### Scenario: Authorized user opens global grid management
- **WHEN** a user with the dedicated global grid administration authorization opens the CompetVet global grid management page
- **THEN** the user is allowed to access the page and load the global grid administration interface

### Requirement: Global grid editing is separated from broader administration
The system SHALL scope this authorization to global grid and criteria administration only.
The system SHALL NOT require the user to hold broader manager-level permissions solely to edit global grids.

#### Scenario: Restricted administrator edits global grids
- **WHEN** a user with the dedicated global grid administration authorization edits a global grid
- **THEN** the edit is permitted without granting unrelated broader administration permissions

### Requirement: Access checks are consistent across page and API operations
The system SHALL apply the same authorization rule to the global grid management page, the grid and criterion update operations, and the delete eligibility checks for global grids and criteria.
The system SHALL prevent unauthorized users from editing or deleting global grids and criteria through either the UI or the webservice layer.

#### Scenario: Unauthorized user attempts global grid update
- **WHEN** a user without the dedicated global grid administration authorization attempts to access or update global grids
- **THEN** the system refuses the action consistently across the page and API operations
