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
use mod_competvet\competvet;
use mod_competvet\notifications;
use core_user;
use moodle_url;
use mod_competvet\local\persistent\todo;

/**
 * Class student_graded
 *
 * @package    mod_competvet
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class student_graded extends \core\task\adhoc_task {

    /** @var string Task name */
    private $taskname = 'student_graded';

    /**
     * Get the name of the task.
     *
     * @return string Task name shown in admin screens.
     */
    public function get_name() {
        return get_string('notification:' . $this->taskname, 'mod_competvet');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        // Check if this task is enabled.
        if (!get_config('mod_competvet', 'student_graded_enabled')) {
            return;
        }
        $data = $this->get_custom_data();
        $cmid = $data->cmid;
        $competvet = competvet::get_from_cmid($cmid);
        $studentid = $data->studentid;
        $student = core_user::get_user($studentid);
        if (!$student) {
            return;
        }

        // First clear out all pending tasks for this user.
        $todos = todo::get_records_select(
            'status = :status AND targetuserid = :targetuserid AND planningid = :planningid',
            ['status' => todo::STATUS_PENDING, 'targetuserid' => $studentid, 'planningid' => $data->planningid]
        );
        foreach ($todos as $todo) {
            $todo->delete();
        }

        // Send the email.
        $recipients[]= $student;
        $context = $this->get_email_context($competvet, $student);
        notifications::send_email($this->taskname, $student->id, $competvet->get_instance_id(), $recipients, $context);
    }

    /**
     * Get the email context.
     *
     * @param competvet $competvet
     * @param $student
     * @return object
     */
    private function get_email_context(competvet $competvet, $student) {
        $competvetname = $competvet->get_instance()->name;

        $context = [];
        $context['subject'] = get_string('email:student_graded:subject', 'mod_competvet', $competvetname);
        $context['situationname'] = $competvet->get_situation()->get('shortname');
        $context['fullname'] = fullname($student);
        return $context;
    }
}
