<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Prints an instance of mod_competvet.
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_competvet\competvet;
use mod_competvet\output\grading\app;
use mod_competvet\utils;

require(__DIR__ . '/../../config.php');

global $DB, $PAGE, $OUTPUT, $USER;

[$cm, $course, $moduleinstance] = utils::page_requirements('view');

$modulecontext = context_module::instance($cm->id);

$PAGE->set_pagelayout('embedded');

$PAGE->activityheader->disable();

$courseshortname = $modulecontext->get_course_context()->get_context_name(false, true);
$args = [
    'contextname' => $modulecontext->get_context_name(false, true),
    'subpage' => get_string('grading', 'assign'),
];
$title = get_string('subpagetitle', 'assign', $args);
$title = $courseshortname . ': ' . $title;
$PAGE->set_title($title);

$compevet = competvet::get_from_context($modulecontext);
$gradingapp = new app($USER->id, 0, $compevet);
echo $OUTPUT->header();

echo $OUTPUT->heading($moduleinstance->name);
echo $OUTPUT->render($gradingapp);

echo $OUTPUT->footer();
