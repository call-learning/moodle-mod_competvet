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

$cmid = required_param('id', PARAM_INT);
$competvet = \mod_competvet\competvet::get_from_cmid($cmid);
$context = $competvet->get_context();
require_login($competvet->get_course(), true, $competvet->get_course_module());

$userid = optional_param('userid', $USER->id, PARAM_INT);
$reportid = optional_param('reportid', 'caselogentries', PARAM_ALPHANUM);

$currenturl = new moodle_url('/mod/competvet/index.php', ['id' => $cmid, 'userid' => $userid, 'reportid' => $reportid]);
$PAGE->set_url($currenturl);
$PAGE->set_context($context);
$pagetitle = get_string('report:' . $reportid, 'mod_competvet').  ':'. format_string($competvet->name);
$PAGE->set_title($pagetitle);
$returnto = optional_param('returnurl', null, PARAM_URL);
if ($returnto) {
    $PAGE->set_button($OUTPUT->single_button(new moodle_url($returnto), get_string('back')));
}
echo $OUTPUT->header();
echo $OUTPUT->heading($pagetitle);
if ($reportid == 'caselogentries') {
    $planningid = required_param('planningid', PARAM_INT);
    $studentid = optional_param('studentid', $USER->id, PARAM_INT);
    $report = \core_reportbuilder\system_report_factory::create(
        \mod_competvet\reportbuilder\local\systemreports\case_entries::class,
        $context,
        '',
        '',
        0,
        [
            'planningid' => $planningid,
            'studentid' => $studentid,
        ]
    );
}
if (!empty($report)) {
    echo $report->output();
}
echo $OUTPUT->footer();
