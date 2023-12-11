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
 * Display information about all observations
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_reportbuilder\datasource;
use core_reportbuilder\local\helpers\report as helper;
use core_reportbuilder\local\models\report as report_model;
use mod_competvet\competvet;
use mod_competvet\reportbuilder\datasource\observations;
use mod_competvet\reportbuilder\local\helpers\data_retriever_helper;

require(__DIR__ . '/../../../config.php');
global $PAGE, $DB, $OUTPUT, $USER;

require_login();
$userid = optional_param('userid', $USER->id, PARAM_INT);

$context = context_system::instance();
$currenturl = new moodle_url('/mod/competvet/mobileview/observer.php', ['userid' => $userid]);
$PAGE->set_url($currenturl);
$pagetitle = get_string('allobservations', 'mod_competvet');
if ($userid != $USER->id) {
    $user = core_user::get_user($userid);
    $pagetitle = get_string('allobservationsforuser', 'mod_competvet', fullname($user));
}
$PAGE->set_context($context);
$PAGE->set_title($pagetitle);
$reportdata = [
    'type' => datasource::TYPE_CUSTOM_REPORT,
    'source' => mod_competvet\reportbuilder\datasource\observations::class,
    'component' => competvet::COMPONENT_NAME,
    'area' => 'allobservationspages',
    'name' => get_string('allobservations', 'mod_competvet'),
];
$existingreport = report_model::get_record($reportdata);
if (empty($existingreport)) {
    $existingreport = helper::create_report((object) $reportdata, true);
}
helper::add_report_column($existingreport->get('id'), 'observation:observationid');
$datasource = new observations($existingreport);
data_retriever_helper::get_data_from_custom_report(
    $datasource,
    ['userid' => $userid],
    [],
    0
);
echo $OUTPUT->header();

echo $OUTPUT->heading($pagetitle);

echo $OUTPUT->footer();
