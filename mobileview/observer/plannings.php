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

use mod_competvet\competvet;
use mod_competvet\local\persistent\planning;

require(__DIR__ . '/../../../../config.php');
global $PAGE, $DB, $OUTPUT, $USER;

require_login();
$situationid = required_param('situationid', PARAM_INT);
$userid = optional_param('userid', $USER->id, PARAM_INT);

$context = context_system::instance();
$PAGE->set_context($context);
$currenturl = new moodle_url('/mod/competvet/mobileview/observer/planning.php', ['userid' => $userid]);
$PAGE->set_url($currenturl);

$plannings = mod_competvet\local\api\plannings::get_plannings_for_situation_id($situationid, $userid, true);
$competvet = competvet::get_from_situation_id($situationid);
$PAGE->set_button(
    $OUTPUT->single_button(new moodle_url('/mod/competvet/mobileview/observer/index.php', ['userid' => $userid]),
        get_string('back'), 'get'));
echo $OUTPUT->header();

echo $OUTPUT->heading(format_text($competvet->get_instance()->name, FORMAT_HTML));
foreach ([planning::STATUS_CURRENT, planning::STATUS_OBSERVER_LATE, planning::STATUS_OBSERVER_COMPLETED] as $status) {
    $statustitle = get_string('planningstatus:' . planning::STATUS[$status], 'mod_competvet');
    print_collapsible_region_start('card', 'status' . sha1($status), $statustitle, '', true);
    foreach ($plannings as $planning) {
        if ($planning['status'] != $status) {
            continue;
        }
        $dates = get_string('mobileview:planningdates', 'mod_competvet', [
                'startdate' => planning::get_planning_date_string($planning['startdate']),
                'enddate' => planning::get_planning_date_string($planning['enddate']),
            ]
        );
        $groupinfo = get_string('mobileview:observer:groupinfo', 'mod_competvet', [
                'groupname' => $planning['groupname'],
                'nbstudentstoeval' => $planning['stats']['nbtoeval'],
            ]
        );
        /** @var core_renderer $OUTPUT */
        echo $OUTPUT->container_start('card my-2 p-1', 'planning');
        echo $OUTPUT->container($dates, 'font-weight-bold', 'planningdates');
        $grouplink = new moodle_url('/mod/competvet/mobileview/observer/group.php', [
            'userid' => $userid,
            'planningid' => $planning['id'],
            'group' => $planning['groupid'],
        ]);
        echo $OUTPUT->container(html_writer::link($grouplink, $groupinfo), 'text-small', 'planningdates');
        echo $OUTPUT->container_end();
    }
    print_collapsible_region_end();
}

echo $OUTPUT->footer();
