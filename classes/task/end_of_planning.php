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
use mod_competvet\local\api\grading as grading_api;
use core_user;
use mod_competvet\utils;

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
        $clock = \core\di::get(\core\clock::class);
        $lastinterval = strtotime('-7 day');

        // Get plannings that are near the end, not fully evaluated, and have no sent notification.
        $plannings = $DB->get_records_sql("
            SELECT p.*
            FROM {competvet_planning} p
            LEFT JOIN {competvet_notification} n
            ON p.id = n.notifid AND n.notification = :notification
            WHERE p.enddate > :lastinterval AND p.enddate < :now
            AND n.id IS NULL
        ", [
            'lastinterval' => $lastinterval,  // Plannings ending in the last time we checked.
            'now' => $clock->time(),
            'notification' => $this->taskname,
        ]);
        // Send a reminder email for each planning that does not have a notification.
        foreach ($plannings as $planning) {
            $ungradedstudents = $this->get_ungraded_students($planning->id);
            if (empty($ungradedstudents)) {
                continue;
            }
            $recipients = utils::get_users_with_role(competvet::ROLE_EVALUATOR, $planning->situationid);
            if (empty($recipients)) {
                continue;
            }
            $context = [];
            $context['enddate'] = userdate($planning->enddate);
            $context['students'] = implode('', array_map(function($student) {
                return '<li>' . fullname($student) . '</li>';
            }, $ungradedstudents));

            $competvet = competvet::get_from_situation_id($planning->situationid);
            notifications::setnotification($this->taskname, $planning->id, $competvet->get_instance_id(), $recipients, $context);
        }
    }

    /**
     * Get ungraded students for the planning.
     *
     * @param int $planningid
     * @return array List of students that have not been graded.
     */
    private function get_ungraded_students(int $planningid): array {
        global $USER;
        $planninginfo = grading_api::get_planning_infos_for_grading([$planningid], $USER->id);
        if (empty($planninginfo)) {
            return [];
        }
        $studenstwithinfo = [];
        $students = $planninginfo[0]['stats']['students'];
        foreach ($students as $student) {
            if (empty($student->grade)) {
                $studenstwithinfo[] = core_user::get_user($student->id);
            }
        }
        return $studenstwithinfo;
    }
}
