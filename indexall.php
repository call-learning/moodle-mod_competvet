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
 * Display information about all the mod_competvet modules in the system like we would do
 * on the app.
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_reportbuilder\form\card_view;
use mod_competvet\competvet;
use mod_competvet\reportbuilder\local\systemreports\situations_per_user;

require(__DIR__ . '/../../config.php');
global $PAGE, $DB, $OUTPUT, $USER;

require_login();
$userid = optional_param('userid', $USER->id, PARAM_INT);

$context = context_system::instance();
$currenturl = new moodle_url('/course/view.php', ['userid' => $userid]);
$PAGE->set_url($currenturl);
$pagetitle = get_string('allmysituations', 'mod_competvet');
if ($userid != $USER->id) {
    $user = core_user::get_user($userid);
    $pagetitle = get_string('allusersituations', 'mod_competvet', fullname($user));
}
$PAGE->set_context($context);
$PAGE->set_title($pagetitle);

$situationreport = \core_reportbuilder\system_report_factory::create(
    situations_per_user::class,
    $context,
    competvet::COMPONENT_NAME,
    '',
    0,
    [
        'userid' => $userid,
        'cardview' => true,
    ]
);
echo $OUTPUT->header();

echo $OUTPUT->heading($pagetitle);
echo $situationreport->output();
echo $OUTPUT->footer();
