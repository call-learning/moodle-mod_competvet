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
 * Display information about all the mod_competvet modules in the requested course.
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_competvet\local\persistent\situation;
use mod_competvet\reportbuilder\local\systemreports\situations;
require(__DIR__ . '/../../config.php');
global $PAGE, $DB, $OUTPUT, $USER;

$id = optional_param('id', SITEID, PARAM_INT);
$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);
if ($id == SITEID) {
    $context = context_system::instance();
    require_login();
} else {
    $context = context_system::instance();
    require_course_login($course);
}

$userid = optional_param('userid', $USER->id, PARAM_INT);

$context = context_system::instance();
$currenttab = optional_param('currenttab', 'situations', PARAM_ALPHA);
$tabtree = [];
$tabs = [
    'situations' => [
        'url' => new moodle_url('/mod/competvet/index.php', ['id' => $id, 'currenttab' => 'situations']),
        'label' => get_string('allmysituations', 'mod_competvet'),
    ],
    'todo' => [
        'url' => new moodle_url('/mod/competvet/index.php', ['id' => $id,  'currenttab' => 'todo']),
        'label' => get_string('todos', 'mod_competvet'),
    ],
];
foreach($tabs as $id => $tab) {
    $tabtree[] = new tabobject(
        $id,
        $tab['url'],
        $tab['label'],
    );
}

$currenturl = new moodle_url($tabs[$currenttab]['url']);
$pagetitle = $tabs[$currenttab]['label'];
$PAGE->set_url($currenturl);
$PAGE->set_context($context);
$PAGE->set_title($pagetitle);

echo $OUTPUT->header();
echo $OUTPUT->heading($pagetitle);
echo $OUTPUT->tabtree($tabtree, $currenttab);
switch($currenttab) {
    case 'situations':
        if ($id != SITEID) {
            $situationsid = situation::get_all_situations_in_course_id_for($userid, $course->id);
        } else {
            $situationsid = situation::get_all_situations_id_for($userid);
        }
        $report = \core_reportbuilder\system_report_factory::create(
            situations::class,
            $context,
            '',
            '',
            0,
            [
                'onlyforsituationsid' => join(",", $situationsid),
            ],
        );
        break;
    case 'todo':
        $report = \core_reportbuilder\system_report_factory::create(
            \mod_competvet\reportbuilder\local\systemreports\todos::class,
            $context,
            '',
            '',
            0,
        );
        break;
}
echo $report->output();
echo $OUTPUT->footer();
