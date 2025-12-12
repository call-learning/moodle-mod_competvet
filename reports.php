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

use core_reportbuilder\manager;
use core_reportbuilder\permission;
use core_reportbuilder\system_report_factory;
use mod_competvet\reportbuilder\local\systemreports\competvet_report_list;

require(__DIR__ . '/../../config.php');
global $PAGE, $DB, $OUTPUT, $USER;

$cmid = optional_param('id', null, PARAM_INT);
if ($cmid === null) {
    $context = context_system::instance();
    require_login();
    $urlparams = [];
} else {
    $competvet = \mod_competvet\competvet::get_from_cmid($cmid);
    $context = $competvet->get_context();
    require_login($competvet->get_course(), true, $competvet->get_course_module());
    $urlparams = ['id' => $cmid];
}
$reportid = optional_param('reportid', 0, PARAM_INT);
$userid = optional_param('userid', $USER->id, PARAM_INT);
$returnto = optional_param('returnurl', null, PARAM_URL);

if ($userid) {
    $urlparams += ['userid' => $userid];
}
if ($reportid) {
    // We restrict parameters to alphanumext for security.
    $parameters = optional_param_array('parameters', [], PARAM_ALPHANUMEXT);
    $report = manager::get_report_from_id($reportid, $parameters);
    permission::require_can_view_report($report->get_report_persistent());
    $urlparams += ['reportid' => $reportid];
    $reportname = $report->get_report_persistent()->get_formatted_name();
}
if ($returnto) {
    $urlparams += ['returnurl' => $returnto];
}

$currenturl = new moodle_url('/mod/competvet/index.php', $urlparams);
$PAGE->set_url($currenturl);
$PAGE->set_context($context);
$PAGE->navbar->add(get_string('viewreport', 'core_reportbuilder'), $currenturl);
$title = $reportname ?? get_string('viewreport', 'core_reportbuilder');
$PAGE->set_title($title);

if ($returnto) {
    $PAGE->set_button($OUTPUT->single_button(new moodle_url($returnto), get_string('back')));
}
echo $OUTPUT->header();
if (!empty($report)) {
    echo $OUTPUT->heading($title);
    echo $report->output();
} else {
    echo $OUTPUT->heading(get_string('customreports', 'core_reportbuilder'));
    $report = system_report_factory::create(
        competvet_report_list::class,
        context_system::instance()
    );
    echo $report->output();
}
echo $OUTPUT->footer();
