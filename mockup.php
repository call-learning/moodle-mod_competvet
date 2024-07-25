<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * TODO describe file mockup
 *
 * @package    mod_competvet
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_competvet\competvet;
use mod_competvet\utils;

require(__DIR__.'/../../config.php');
global $DB;
// Course module ID.

[$cm, $course, $moduleinstance] = utils::page_requirements('view');

$modulecontext = context_module::instance($cm->id);

$pageurl = new moodle_url('/mod/competvet/mockup.php');

$jsonfile = $CFG->dirroot . '/mod/competvet/data/grading.json';
$grading = json_decode(file_get_contents($jsonfile), true);
$grading['wwwroot'] = $CFG->wwwroot;
$grading['version'] = time();

echo $OUTPUT->header();

echo $OUTPUT->render_from_template('mod_competvet/mockup', $grading);

echo $OUTPUT->footer();

