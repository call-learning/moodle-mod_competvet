## Context

See `proposal.md` for motivation. CompetVet stores many records keyed by Moodle user IDs, then rebuilds display data lazily through `utils::get_user_info()`. The current code already softens one failure mode by returning a generic fallback when a user lookup no longer resolves, which helps historical reads survive. However, the rest of the plugin still mixes two distinct concerns: active membership, which is reconstructed from the current group and role state, and historical record readability, which depends on stored user IDs remaining interpretable after the user disappears. In parallel, the plugin appears not to implement a privacy provider even though it stores user-linked data across observations, certifications, case entries, todos, and notifications, and the current language string claims the opposite.

## Goals / Non-Goals

**Goals:**
- Separate active user membership behavior from historical record readability.
- Define one consistent fallback strategy for deleted users across CompetVet surfaces.
- Specify the privacy contract for CompetVet data export and erasure.
- Keep this change scoped to user deletion and privacy, not group deletion.
- Protect the main user-deletion workflow with regression tests.

**Non-Goals:**
- Redesign the group-history solution already covered by another change.
- Decide broader institutional retention policy beyond what the plugin must implement technically.
- Rewrite every UI to expose rich audit history for deleted users in this planning change.
- Change unrelated role-resolution or group-assignment rules except where required for deleted-user robustness.

## Decisions

### Treat active membership and historical references as separate concerns
Decision: active student listings follow current Moodle group membership and active-user checks, while historical observations and related records remain readable through a deleted-user fallback.

Rationale: these two needs are different. Operational lists should not show deleted users as active students, but historical records should not disappear or break just because the original Moodle user account is gone.

Alternatives considered:
- Remove every record linked to a deleted user immediately. Rejected because it would destroy pedagogical history and bypass privacy-policy nuance.
- Keep deleted users visible in active lists. Rejected because it makes current planning membership misleading.

### Reuse a single fallback identity model for missing users
Decision: define one shared representation for missing or deleted users and reuse it everywhere `get_user_info()`-style resolution occurs.

Rationale: many APIs and views reconstruct `studentinfo`, `observerinfo`, `targetuser`, and similar payloads. If each surface improvises, the plugin will become inconsistent and fragile.

Alternatives considered:
- Handle missing users case by case in each screen. Rejected because the plugin already has many user-info call sites and this would drift quickly.

### Add an explicit privacy policy per CompetVet record family
Decision: specify record-by-record privacy handling instead of relying on accidental behavior from raw foreign keys or generic Moodle account deletion.

Rationale: the plugin stores personal data directly or indirectly. A meaningful privacy implementation requires explicit treatment for observations, certifications, case entries, todos, notifications, and any user-owned derivatives.

Alternatives considered:
- Keep the current "no personal data" declaration. Rejected because it is incompatible with the stored data model.

### Keep privacy erasure compatible with historical readability
Decision: whichever deletion policy is chosen per data family, remaining plugin views must still render safely after privacy operations.

Rationale: privacy compliance must not create secondary integrity bugs in CompetVet. Historical remnants, if retained lawfully, still need a readable fallback.

Alternatives considered:
- Let privacy erasure physically null or delete user links without read-path adjustments. Rejected because many current screens assume user info can always be reconstructed.

## Risks / Trade-offs

- [Privacy policy ambiguity] Some data may be educationally useful but still personal -> Mitigation: force the change to document per-record policy explicitly before implementation.
- [Inconsistent deleted-user display] Multiple APIs may expose slightly different fallback payloads -> Mitigation: centralize fallback semantics and cover representative surfaces in tests.
- [False sense of compliance] Adding metadata alone without export/erasure behavior would be incomplete -> Mitigation: keep privacy provider behavior as part of the same capability, not a later nice-to-have.
- [Historical data loss] Over-aggressive erasure could remove evidence that should instead be anonymized or retained lawfully -> Mitigation: separate policy definition from implementation and validate each record family deliberately.

## Migration Plan

1. Inventory every CompetVet table and API surface that stores or exposes user-linked data.
2. Define the deleted-user fallback contract for read paths and the active-membership filtering contract for current student lists.
3. Define per-record privacy handling for export and erasure requests.
4. Implement or update the privacy provider and any supporting deletion/anonymization helpers.
5. Update application read paths so deleted users do not break historical rendering.
6. Add regression coverage for the evaluated-student deletion workflow and for privacy metadata/export/erasure behavior.
