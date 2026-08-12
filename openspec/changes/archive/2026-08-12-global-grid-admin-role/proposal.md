## Why

Editing global CompetVet grids is currently too tightly coupled to existing administration permissions, which makes it difficult to delegate this task to domain users with restricted rights. This change is needed to provide controlled access to the global-grid settings page without opening the full administration permission set.

## What Changes

- Introduce a dedicated authorization mechanism for editing global CompetVet grids, backed either by a specific system capability or by a role built on that capability.
- Allow authorized users to access the settings or management page for global grids in order to review and modify them.
- Restrict that authorization to the scope of global grids, without automatically granting other broader administration or editing permissions.
- Align access checks across the page, webservices, and delete or update operations on global grids and criteria.

## Capabilities

### New Capabilities
- `global-grid-administration`: Defines how a restricted user can receive access and edit rights for global CompetVet grids.

### Modified Capabilities

## Impact

- Definition of capabilities and any associated role in CompetVet.
- Criteria-management page or settings page for global grids.
- Webservices and access checks around global grids and criteria.
- Documentation and permission tests for restricted global-grid editing.
