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

/**
 * Class student_target
 *
 * @package    mod_competvet
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class student_target extends \core\task\scheduled_task {

    /** @var string Task name */
    private $taskname = 'student_target';

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
     * and sending reminder emails to students who have not met the target.
     */
    public function execute() {
        global $DB;

        // Get plannings that are near the end, not fully evaluated, and have no sent notification.
        $plannings = $DB->get_records_sql("
            SELECT p.*
            FROM {competvet_planning} p
            LEFT JOIN {competvet_notification} n
            ON p.id = n.notifid
            AND (n.notification = :eval OR n.notification = :autoeval OR n.notification = :cert OR n.notification = :list)
            WHERE p.enddate > :now AND p.enddate < :in72hours
            AND n.id IS NULL
        ", [
            'in72hours' => time() + 72 * 3600, // 72 hours from now
            'now' => time(),
            'eval' => $this->taskname . ':eval',
            'autoeval' => $this->taskname . ':autoeval',
            'cert' => $this->taskname . ':cert',
            'list' => $this->taskname . ':list',
        ]);
        foreach ($plannings as $planning) {
            $competvet = competvet::get_from_situation_id($planning->situationid);

            // check if eval is enabled for this situation
            $situation = $competvet->get_situation();
            $modules = [];
            if ($situation->get('haseval')) {
                $modules[] = 'eval';
                $modules[] = 'autoeval';
            }
            if ($situation->get('hascertif')) {
                $modules[] = 'cert';
            }
            if ($situation->get('hascase')) {
                $modules[] = 'list';
            }
            foreach ($modules as $module) {
                $studenttargets = $this->get_student_for_targets($planning->id, $module);
                if (empty($studenttargets)) {
                    continue;
                }
                notifications::send_email($this->taskname . ':' . $module, $planning->id, $competvet->get_instance_id(),
                    $studenttargets, []);
            }
        }
    }

    /**
     * Check if the student has met the target for this module (eval, cert, list)
     *
     * @param object $student
     * @return bool True if the student has met the target.
     */
    private function has_met_target($student, string $module): bool {
        // Check if the student has met the target for this module.
        if ($student->planninginfo['stats'][$module]) {
            $nbdone = $student->planninginfo['stats'][$module]['nbdone'];
            $nbrequired = $student->planninginfo['stats'][$module]['nbrequired'];
            if ($nbdone >= $nbrequired) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get ungraded students for the planning.
     *
     * @param int $planningid
     * @param string $module
     * @return array List of students that have not met the target.
     */
    private function get_student_for_targets(int $planningid, string $module): array {
        global $USER;
        $planninginfo = grading_api::get_planning_infos_for_grading([$planningid], $USER->id);
        if (empty($planninginfo)) {
            return [];
        }
        $studenstwithinfo = [];
        $students = $planninginfo[0]['stats']['students'];
        foreach ($students as $student) {
            if (empty($student->grade)) {
                if ($this->has_met_target($student, $module)) {
                    continue;
                }
                $studenstwithinfo[] = core_user::get_user($student->id);
            }
        }
        return $studenstwithinfo;
    }
}
