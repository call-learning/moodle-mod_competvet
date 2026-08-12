# CompetVet Eval

[![Moodle Plugin CI](https://github.com/call-learning/moodle-mod_competvet/actions/workflows/ci.yml/badge.svg)](https://github.com/call-learning/moodle-mod_competvet/actions/workflows/ci.yml)

## What is CompetVet Eval?

**CompetVet Eval** is an evaluation module designed for French National Veterinary Schools (ENVF). It is an evaluation tool for rotations, allowing the management, tracking, and assessment of students' competencies during their clinical rotations and practical placements. The tool facilitates the structured collection of observations, self-assessments, supervisor evaluations, and the validation of required skills for veterinary training.

Main features:
- Competency tracking by rotation/placement
- Entry of observations and evaluations by students and supervisors
- Validation of achievements and report generation
- Interface tailored to the needs of veterinary schools (ENVF)

## Installation via ZIP file

1. Log in to your Moodle site as an administrator and go to _Site administration > Plugins > Install plugins_.
2. Upload the ZIP file containing the plugin code. Moodle will automatically detect the plugin type.
3. Check the plugin validation report and complete the installation.

## Manual installation

The plugin can also be installed by copying the contents of this directory to:

    {your/moodle/dirroot}/mod/competvet

Then, log in to Moodle as an administrator and go to _Site administration > Notifications_ to finalize the installation.

You can also run the following command to complete the installation from the command line:

    $ php admin/cli/upgrade.php

## Usage

After installation, add a "CompetVet Eval" activity to a course. Configure the rotations, the competencies to be assessed, and the user roles (students, supervisors, observers). Students and supervisors can then enter and validate evaluations directly in Moodle.

### Global grid administration

To delegate global grid maintenance without granting broader site administration rights, create or edit a restricted role at system context and grant it `mod/competvet:manageglobalcriteria`. Assign that role to the selected users at system context. The capability controls access to the global grid page and its grid and criterion operations.

## Changelog

### 2.5.8

- Caselog form versioning: case entries now carry a `versionid` linking them to a specific form schema. Legacy entries are migrated to a "Legacy Caselog" version automatically on upgrade.
- Caselog `get_case_list` API: the `label` field now returns `nom_animal + espece` (e.g. "Rex Chien") instead of `motif_presentation`. The `motif_presentation` value is still available in the full entry payload via `cases::get_entry()`.
- Backup/restore: grids and criteria are now correctly reused by `idnumber` on restore instead of duplicating. Fixed `get_field()` calls that threw on duplicate idnumbers (Fix #811).
- Grid editing integrity hardened: external API and restore process now validate grid/criterion operations more strictly (MDL-874).
- New `mod/competvet:manageglobalcriteria` capability: allows delegated management of global grids and criteria without broader site admin rights. Exposed via activity admin menu and web service (Fix #811).
- Final grade TODO cleanup: `manage_grade` external API and `student_graded` task refactored for cleaner grade management (Fix #530).
- Empty observations purge: new CLI script `cli/purge_empty_observations.php` and API methods to remove observations with no field values (Fix #351).
- Inherited role conflicts fixed: planning and situation role assignment logic corrected to handle inherited roles properly (Fix #598).
- Certification revalidation: rejected certifications now return to pending validation workflow instead of being lost (Fix #805).
- Points-based grade items enforced: grade item creation now validates point-based grading configuration (Fix #843).
- Competency progression report: new report and external API (`get_progression.php`) for tracking student competency progression across plannings. Includes system report, frontend repository method, and progression task (Fix #813).

## License

2023 - CALL Learning - Laurent David <laurent@call-learning.fr>

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program. If not, see <https://www.gnu.org/licenses/>.
