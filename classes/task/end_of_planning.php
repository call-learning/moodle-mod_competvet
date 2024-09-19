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

namespace mod_competvet\task;

use mod_competvet\notifications;
use mod_competvet\competvet;
use mod_competvet\local\api\plannings;
use mod_competvet\local\api\grading as grading_api;
use mod_competvet\utils;
use core_user;
use moodle_url;

/**
 * Class end_of_planning
 *
 * @package    mod_competvet
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Task to send reminder emails to evaluators at the end of a situation.
 */
class end_of_planning extends \core\task\scheduled_task {

    /** @var string Task name */
    private $taskname = 'end_of_planning';

    /**
     * Get the name of the task.
     *
     * @return string Task name shown in admin screens.
     */
    public function get_name() {
        return get_string('notification:' . $this->taskname, 'mod_competvet');
    }

    /**
     * Executes the task by checking for near-end planning periods
     * and sending reminder emails to evaluators if not already sent.
     */
    public function execute() {
        global $DB;

        // Get plannings that are near the end, not fully evaluated, and have no sent notification.
        $plannings = $DB->get_records_sql("
            SELECT p.*
            FROM {competvet_planning} p
            LEFT JOIN {competvet_notification} n
            ON p.id = n.notifid AND n.notification = :notification
            WHERE p.enddate > :oneday AND p.enddate < :now
            AND n.id IS NULL
        ", [
            'oneday' => strtotime('-1 days'),  // Plannings ending within the next 7 days
            'now' => time(),
            'notification' => 'end_of_planning'
        ]);

        // Send a reminder email for each planning that does not have a notification.
        foreach ($plannings as $planning) {
            // TODO, check if observers are okay or not.
            $competvet = competvet::get_from_situation_id($planning->situationid);
            // Get the list of users in this context with the capability 'mod/competvet:cangrade'.
            $modulecontext = $competvet->get_context();
            $recipients = get_users_by_capability($modulecontext, 'mod/competvet:cangrade');

            // Fetch context for the template (students for the planning)
            $context = $this->get_email_context($competvet, $planning);
            // if context is an empty array, no need to send the email
            if (empty($context)) {
                continue;
            }
            // Call notifications to handle the email
            notifications::send_email('end_of_planning', $planning->id, $competvet->get_instance_id(), $recipients, $context);
        }
    }

    /**
     * Get the context data for the email template.
     *
     * @param object $competvet Competvet instance.
     * @param object $planning Planning data.
     * @return array Template context data.
     */
    private function get_email_context($competvet, $planning) {
        global $USER;
        $planninginfo = grading_api::get_planning_infos_for_grading([$planning->id], $USER->id);
        if (empty($planninginfo)) {
            return [];
        }
        $students = $planninginfo[0]['stats']['students'];
        foreach ($students as $student) {
            if (empty($student->grade)) {
                $studenstwithinfo[] = core_user::get_user($student->id);
            }
        }
        if (empty($studenstwithinfo)) {
            return [];
        }
        $competvetname = $competvet->get_instance()->name;

        $studenthtml = array_map(function($student) {
            return '<li>' . fullname($student) . '</li>';
        }, $studenstwithinfo);

        $studenthtml = implode('', $studenthtml);

        return [
            'situation' => $competvetname,
            'enddate' => userdate($planning->enddate),
            'students' => $studenthtml
        ];
    }
}