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

use core_user;
use mod_competvet\competvet;
use mod_competvet\local\api\grading as grading_api;
use mod_competvet\notifications;

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
        $notifications = $this->get_notifications_to_send();
        $this->send_notifications($notifications);
    }

    /**
     * Get the notifications to be sent.
     *
     * @return array
     */
    public function get_notifications_to_send() {
        $notifications = [];
        global $DB;
        $plannings = $DB->get_records_sql("
            SELECT p.*
            FROM {competvet_planning} p
            LEFT JOIN {competvet_notification} n
            ON p.id = n.notifid AND n.notification = :notification
            WHERE p.enddate > :oneday AND p.enddate < :now
            AND n.id IS NULL
        ", [
            'oneday' => strtotime('-1 days'),  // Plannings ending in the next 24 hours.
            'now' => time(),
            'notification' => $this->taskname,
        ]);
        foreach ($plannings as $planning) {
            $ungradedstudents = $this->get_ungraded_students($planning->id);
            if (empty($ungradedstudents)) {
                return $notifications;
            }
            $competvet = competvet::get_from_situation_id($planning->situationid);
            $modulecontext = $competvet->get_context();
            $recipients = get_users_by_capability($modulecontext, 'mod/competvet:cangrade');

            $context = [];
            $context['enddate'] = userdate($planning->enddate);
            $context['students'] = implode('', array_map(function($student) {
                return '<li>' . fullname($student) . '</li>';
            }, $ungradedstudents));

            $notifications[] = [
                'planning' => $planning,
                'competvet' => $competvet,
                'recipients' => $recipients,
                'context' => $context
            ];
        }

        return $notifications;
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

    /**
     * Send notifications by email.
     *
     * @param array $notifications
     */
    public function send_notifications($notifications) {
        foreach ($notifications as $notification) {
            notifications::send_email(
                $this->taskname,
                $notification['planning']->id,
                $notification['competvet']->get_instance_id(),
                $notification['recipients'],
                $notification['context']
            );
        }
    }
}
