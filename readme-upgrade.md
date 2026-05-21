# mod_competvet — Upgrade Notes (Moodle 4.x → 5.0)

Plugin version: **2.5.6** (20250090402), maturity ALPHA, requires Moodle ≥ 4.1

---

## Summary

| Area | Status | Work required |
|---|---|---|
| Mustache templates — BS4 utility classes | 🔴 ~15 templates | ~70+ hits |
| Mustache templates — BS4 data attributes | 🔴 ~10 templates | ~35 hits |
| PHP — ReportBuilder `get_default_table_aliases()` | 🔴 BREAKING — 8 entities | Method rename + return format change |
| PHP — External API: legacy `externallib.php` | 🔴 BREAKING — 31 files | Migrate to `core_external` namespace |
| SCSS | ⚠️ Check | Contains `font-weight` values (CSS property, not BS class — OK) |
| PHPUnit tests | ⚠️ Review | PHPUnit upgraded to 11.4 in Moodle 5.0 |

**Estimated effort: 30–50 hours**

This plugin has the largest upgrade footprint of all audited plugins. Two separate breaking PHP
changes affect it simultaneously.

---

## 1. ReportBuilder Entities — BREAKING CHANGE (Moodle 5.0 / MDL-80430)

**All 8 entities** still use the removed `get_default_table_aliases(): array` method.

### Affected entity files

| Entity file | Notes |
|---|---|
| `classes/reportbuilder/local/entities/observation.php` | |
| `classes/reportbuilder/local/entities/situation.php` | |
| `classes/reportbuilder/local/entities/criterion.php` | |
| `classes/reportbuilder/local/entities/todo.php` | |
| `classes/reportbuilder/local/entities/grid.php` | |
| `classes/reportbuilder/local/entities/case_entry.php` | |
| `classes/reportbuilder/local/entities/observation_comment.php` | |
| `classes/reportbuilder/local/entities/planning.php` | |

### Migration pattern

**Old format:**
```php
protected function get_default_table_aliases(): array {
    return [
        'competvet_obs' => 'obs',    // key = TABLE, value = ALIAS
        'user'          => 'u',
    ];
}
```

**New format:**
```php
protected function get_default_tables(): array {
    return ['competvet_obs', 'user'];   // table names only — aliases auto-assigned
}
```

After renaming, any code calling `$this->get_table_alias('tablename')` continues to work
(the base class generates aliases from table names). However, verify that SQL in columns and
filters references the correctly generated alias and not a hardcoded old alias string.

---

## 2. External API — BREAKING CHANGE (Moodle 5.0)

**31 external function files** still use the legacy includes-based pattern:
```php
require_once("$CFG->libdir/externallib.php");
```
`lib/externallib.php` is not removed yet in 5.0 but is deprecated; the classes it provides have
been replaced by the `core_external` namespace (available since Moodle 4.2). This should be
migrated now to avoid breakage in Moodle 5.1/6.0.

### Old pattern (in every file)
```php
require_once("$CFG->libdir/externallib.php");
// ...
class foo extends external_api { ... }
```

### New pattern
```php
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_single_structure;
use core_external\external_multiple_structure;
// (import only what you use)

class foo extends external_api { ... }
```

Remove the `require_once` line entirely — `core_external` classes are autoloaded via the
namespace.

### Scale

31 files to update: `validate_certification.php`, `save_plannings.php`, `set_subgrade.php`,
`get_json.php`, `get_cases.php`, and 26 others in `classes/external/`.

---

## 3. Mustache Templates — Bootstrap 4 Utility Classes

The plugin has a large template tree with many BS4 spacing classes throughout. The following
is a structured breakdown by file.

### 3.1 `templates/grading/tabs/gradetabs.mustache`
| Line | Current | Replace with |
|---|---|---|
| 29 | `ml-3` | `ms-3` |
| 49 | `mr-3` | `me-3` |

### 3.2 `templates/grading/components/evaluations-grading.mustache`
| Line | Current | Replace with |
|---|---|---|
| 61, 68, 125 | `ml-auto` | `ms-auto` |
| 85 | `custom-control custom-checkbox` | `form-check` (structural) |
| (86) | `custom-control-input` | `form-check-input` |
| 98 | `custom-control-label` | `form-check-label` |
| 152 | `pr-0` | `pe-0` |
| 167 | `ml-auto` | `ms-auto` |

### 3.3 `templates/grading/components/globalgrade.mustache`
| Line | Current | Replace with |
|---|---|---|
| 38 | `ml-5` | `ms-5` |

### 3.4 `templates/grading/components/list-results.mustache`
| Line | Current | Replace with |
|---|---|---|
| 80 | `mr-2` | `me-2` |

### 3.5 `templates/grading/components/list-grading.mustache`
| Line | Current | Replace with |
|---|---|---|
| 75 | `custom-control custom-checkbox` | `form-check` (structural) |
| 76 | `custom-control-input` | `form-check-input` |
| 77 | `custom-control-label font-weight-bold` | `form-check-label fw-bold` |
| 86 | `custom-control custom-checkbox` | `form-check` (structural) |
| (87) | `custom-control-input` | `form-check-input` |
| 100 | `custom-control-label` | `form-check-label` |
| 123 | `ml-auto` | `ms-auto` |
| 148 | `pr-0` | `pe-0` |
| 163 | `ml-auto` | `ms-auto` |

### 3.6 `templates/grading/components/certification-results.mustache`
| Line | Current | Replace with |
|---|---|---|
| 84 | `pl-5 ml-2` | `ps-5 ms-2` |
| 94 | `ml-2` | `ms-2` |
| 151, 155, 161, 165, 169 | `sr-only` | `visually-hidden` |

### 3.7 `templates/grading/components/evaluation-results.mustache`
| Line | Current | Replace with |
|---|---|---|
| 107, 126, 151, 170, 185 | `no-gutters` | `g-0` |
| 138, 193 | `sr-only` (inside `div`) | `visually-hidden` |

### 3.8 `templates/grading/components/certification-grading.mustache`
| Line | Current | Replace with |
|---|---|---|
| 55, 63 | `ml-auto` | `ms-auto` |
| 74 | `pr-md-5 mr-md-5` | `pe-md-5 me-md-5` |
| 75 | `pr-md-5` | `pe-md-5` |

### 3.9 `templates/grading/components/user-header.mustache`
| Line | Current | Replace with |
|---|---|---|
| 27 | `mr-3` | `me-3` |

### 3.10 `templates/grading/auto-region/certifs.mustache`
| Line | Current | Replace with |
|---|---|---|
| 71 | `mr-1` | `me-1` |

### 3.11 `templates/expandcollapse.mustache`
| Line | Current | Replace with |
|---|---|---|
| 32 | `mr-1` | `me-1` |

### 3.12 `templates/local/input_type_range.mustache`
| Line | Current | Replace with |
|---|---|---|
| 33 | `ml-2` | `ms-2` |

### 3.13 `templates/manager/todos.mustache`
| Line | Current | Replace with |
|---|---|---|
| 43, 60 | `ml-2` | `ms-2` |

### 3.14 `templates/manager/planning/pause_item.mustache`
| Line | Current | Replace with |
|---|---|---|
| 68, 71, 76 | `mr-1` | `me-1` |

### 3.15 `templates/manager/planning/planning_item.mustache`
| Line | Current | Replace with |
|---|---|---|
| 69, 72, 77 | `mr-1` | `me-1` |
| 78 | `mr-1 ml-auto` | `me-1 ms-auto` |

### 3.16 `templates/manager/notifications.mustache`
| Line | Current | Replace with |
|---|---|---|
| 41, 55 | `ml-2` | `ms-2` |
| 105 | `mr-2` | `me-2` |
| 126 | `ml-2` | `ms-2` |

### 3.17 `templates/manager/criteria/criterion.mustache`
| Line | Current | Replace with |
|---|---|---|
| 60, 63, 69, 94 | `mr-1` | `me-1` |

### 3.18 `templates/view/student_evaluations.mustache`
| Line | Current | Replace with |
|---|---|---|
| 35, 52 | `no-gutters` | `g-0` |
| 40 | `font-weight-bold` | `fw-bold` |
| 41 | `mr-2` | `me-2` |
| 53 | `font-weight-bold` | `fw-bold` |
| 59 | `sr-only` | `visually-hidden` |

### 3.19 `templates/view/planning.mustache`
| Line | Current | Replace with |
|---|---|---|
| 63 | `btn-block text-left` | `w-100 text-start` |

### 3.20 `templates/view/user_info.mustache`
| Line | Current | Replace with |
|---|---|---|
| 48 | `badge badge-secondary` | `badge text-bg-secondary` |
| 51 | `ml-3` | `ms-3` |
| 58 | `badge badge-primary badge-pill` | `badge text-bg-primary rounded-pill` |

### 3.21 `templates/view/observation_card.mustache`
| Line | Current | Replace with |
|---|---|---|
| 29 | `ml-0` | `ms-0` |

### 3.22 `templates/view/plannings.mustache`
| Line | Current | Replace with |
|---|---|---|
| 77, 92, 107 | `sr-only` | `visually-hidden` |
| 90, 105 | `ml-lg-3` (2×) | `ms-lg-3` |
| 113 | `sr-only` | `visually-hidden` |
| 117–120 | `custom-control custom-checkbox` / `custom-control-input` / `custom-control-label` | `form-check` / `form-check-input` / `form-check-label` (structural) |
| 123 | `ml-lg-auto` | `ms-lg-auto` |
| 148 | `ml-2` | `ms-2` |
| 149 | `badge badge-info` | `badge text-bg-info` |
| 150 | `ml-auto` | `ms-auto` |
| 168, 177, 186, 195 | `badge {{#pass}}badge-success{{/pass}}{{^pass}}badge-secondary{{/pass}} badge-pill` | `badge {{#pass}}text-bg-success{{/pass}}{{^pass}}text-bg-secondary{{/pass}} rounded-pill` |
| 203, 210 | `badge badge-info … badge-pill` | `badge text-bg-info … rounded-pill` |
| 222, 232 | `mr-2` | `me-2` |
| 251 | `mr-1` | `me-1` |

### 3.23 `templates/view/student_eval.mustache`
| Line | Current | Replace with |
|---|---|---|
| 46 | `ml-0` | `ms-0` |
| 55 | `ml-auto` | `ms-auto` |

---

## 4. Mustache Templates — Bootstrap 4 Data Attributes

~35 attribute occurrences across ~10 templates. The pattern is consistent:

| Old | New |
|---|---|
| `data-toggle="tab"` | `data-bs-toggle="tab"` |
| `data-toggle="modal"` | `data-bs-toggle="modal"` |
| `data-toggle="collapse"` | `data-bs-toggle="collapse"` |
| `data-toggle="dropdown"` | `data-bs-toggle="dropdown"` |
| `data-toggle="tooltip"` | `data-bs-toggle="tooltip"` |
| `data-toggle="popover"` | `data-bs-toggle="popover"` |
| `data-target="#..."` | `data-bs-target="#..."` |
| `data-dismiss="modal"` | `data-bs-dismiss="modal"` |
| `data-placement="..."` | `data-bs-placement="..."` |
| `data-html="true"` | `data-bs-html="true"` |
| `data-content="..."` | `data-bs-content="..."` |
| `data-parent="#..."` | `data-bs-parent="#..."` |

### Affected templates

| Template | Widgets |
|---|---|
| `grading/tabs/gradetabs.mustache` (lines 30, 40, 50) | 3× `data-toggle="tab"` + `data-target` |
| `grading/debugmodal.mustache` (lines 28, 36, 44) | `data-toggle="modal"`, `data-target`, 2× `data-dismiss="modal"` |
| `grading/components/evaluations-grading.mustache` (line 136) | `data-target` |
| `grading/components/globalgrade.mustache` (lines 38, 47) | `data-toggle="popover"`, `data-placement`, `data-html`, `data-content`; `data-target` |
| `grading/components/list-grading.mustache` (line 134) | `data-target` |
| `grading/components/evaluation-results.mustache` (lines 98, 117, 176) | `data-toggle="collapse"`, 2× `data-toggle="tooltip"` |
| `grading/components/user-navigation.mustache` (line 32) | `data-toggle="dropdown"` |
| `expandcollapse.mustache` (line 28) | `data-toggle="collapse"` |
| `manager/planning.mustache` (line 98) | `data-toggle="collapse"` |
| `manager/notifications.mustache` (lines 29, 43, 57, 87, 96, 108) | `data-toggle="dropdown"` ×3, `data-toggle="modal"` + `data-target`, `data-dismiss="modal"` ×2 |
| `manager/criteria/grids.mustache` (line 50) | `data-toggle="collapse"` |
| `view/student_evaluations.mustache` (line 41) | `data-toggle="tooltip"` |
| `view/planning.mustache` (lines 63, 69) | `data-toggle="collapse"` + `data-target`; `data-parent` |
| `view/plannings.mustache` (lines 168, 177, 186, 195, 203, 210) | `data-toggle="tooltip"` ×6 |

Note: `data-parent` on a collapsible item (accordion behaviour) maps to `data-bs-parent` in BS5.

---

## 5. SCSS

`scss/styles.scss` uses only custom CSS selectors and property values (e.g. `font-weight: 600`).
No Bootstrap utility classes or BS4 variables. No changes required.

---

## 6. Activity Module — New Optional Integration (Moodle 5.0)

Moodle 5.0 introduces a course overview integration for activity plugins (MDL-83872). Consider
implementing the `courseformat\overview` class to provide student progress data in course
overviews. The plugin already has `classes/courseformat/overview.php` — verify it follows the
5.0 API contract.

---

## 7. PHP Server Requirements (Moodle 5.0)

| Requirement | Moodle 4.x | Moodle 5.0 |
|---|---|---|
| PHP | ≥ 8.1 | ≥ **8.2** |
| PHP sodium | optional | **required** |
| PostgreSQL | ≥ 13 | ≥ **14** |
| MySQL | ≥ 8.0 | ≥ **8.4** |
| MariaDB | ≥ 10.6 | ≥ **10.11** |

---

## 8. PHPUnit Tests

PHPUnit upgraded to **11.4** in Moodle 5.0:

- `setUp`/`tearDown` must declare `void` return type
- Mock methods `getMockClass()`, `getMock()`, `getMockFromWsdl()` — REMOVED
- Configuration enables `failOnDeprecation` — all deprecation notices in tests will fail
- `\externallib_advanced_testcase` → `\advanced_testcase` (from `core_external`)

---

## 9. Work Estimate

| Task | Hours |
|---|---|
| Fix 8 PHP ReportBuilder entities (`get_default_table_aliases` → `get_default_tables`) | 4–6 h |
| Migrate 31 external API files to `core_external` namespace | 8–12 h |
| Migrate ~23 templates — BS4 utility classes (~70+ hits, 4× structural rewrites) | 7–10 h |
| Migrate ~14 templates — BS4 data attributes (~35 hits) | 4–5 h |
| PHPUnit test review and fixes | 2–4 h |
| Integration testing (tabs, modals, collapse, dropdowns, tooltips, popovers, reportbuilder, grading) | 5–8 h |
| Update `$plugin->requires` and `$plugin->maturity` from ALPHA to appropriate level | 0.5 h |
| **Total** | **30.5–45.5 h** |

**Recommended order:**
1. PHP entity fix (step 1) — unblocks reportbuilder testing
2. External API migration (step 2) — foundational, unblocks all AJAX operations
3. Template BS4 data attributes (step 4) — all interactive JS components blocked until done
4. Template BS4 class renames (step 3) — visual polish, can be done incrementally
