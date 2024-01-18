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

use mod_competvet\local\api\plannings;
use mod_competvet\output\view\base;
use mod_competvet\utils;

require(__DIR__ . '/../../config.php');

global $DB, $PAGE, $OUTPUT, $USER;

[$cm, $course, $moduleinstance] = utils::page_requirements('view');

$modulecontext = context_module::instance($cm->id);

$event = \mod_competvet\event\course_module_viewed::create([
    'objectid' => $moduleinstance->id,
    'context' => $modulecontext,
]);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('competvet', $moduleinstance);
$event->trigger();

$PAGE->set_context($modulecontext);
$widget = base::factory($USER->id);
$widget->set_data();
$widget->check_access();
$renderer = $PAGE->get_renderer('mod_competvet');
echo $OUTPUT->header();
echo $OUTPUT->heading($moduleinstance->name);

$gradebutton = new single_button(
    new moodle_url(
        '/mod/competvet/grading.php',
        ['id' => $cm->id]
    ),
    get_string('gradeverb')
);
echo $OUTPUT->box_start('generalbox boxaligncenter', 'intro');
echo $OUTPUT->render($gradebutton);
$backbutton = $widget->get_back_button();
if (!empty($backbutton)) {
    echo $OUTPUT->container($OUTPUT->render($backbutton), 'float-right');
}
echo $OUTPUT->box_end('generalbox boxaligncenter', 'intro');
echo $renderer->render($widget);
echo $OUTPUT->footer();
