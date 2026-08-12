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

### 2.5.9

- Certification validation rejections now return declarations to a pending validation workflow. Review and, if necessary, adapt or revalidate mobile application consumers of certification status.

## License

2023 - CALL Learning - Laurent David <laurent@call-learning.fr>

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program. If not, see <https://www.gnu.org/licenses/>.
