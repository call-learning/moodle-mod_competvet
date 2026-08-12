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

namespace mod_competvet\local\tasks;

use advanced_testcase;
use core_user;
use mod_competvet\competvet;
use mod_competvet\local\persistent\planning;
use mod_competvet\local\persistent\situation;
use mod_competvet\local\persistent\todo;
use mod_competvet\task\student_graded;
use mod_competvet\tests\test_data_definition;

/**
 * Tests for the student graded task.
 *
 * @package    mod_competvet
 * @category   test
 * @copyright 2026 CALL Learning <contact@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\mod_competvet\task\student_graded::class)]
final class student_graded_task_test extends advanced_testcase {
    use test_data_definition;

    /**
     * Set up the test fixture.
     *
     * @return void
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->prepare_scenario('set_5');
    }

    /**
     * Test that grading clears only pending todos for the graded student and planning.
     *
     * @return void
     */
    public function test_student_graded_clears_scoped_todos(): void {
        set_config('student_graded_enabled', 0, 'mod_competvet');

        [$student, $planning, $competvet] = $this->get_grade_context();
        $otherplanning = planning::get_records();
        $otherplanning = array_values(array_filter(
            $otherplanning,
            fn(planning $candidate): bool => $candidate->get('id') !== $planning->get('id')
        ))[0];
        $this->create_pending_todo($student->id, $otherplanning->get('id'));
        $this->create_pending_todo(core_user::get_user_by_username('student2')->id, $planning->get('id'));
        $otherplanningtodos = todo::count_records([
            'targetuserid' => $student->id,
            'planningid' => $otherplanning->get('id'),
            'status' => todo::STATUS_PENDING,
        ]);
        $otherstudenttodos = todo::count_records([
            'targetuserid' => core_user::get_user_by_username('student2')->id,
            'planningid' => $planning->get('id'),
            'status' => todo::STATUS_PENDING,
        ]);

        $task = $this->create_task($student->id, $planning->get('id'), $competvet->get_course_module_id());
        $task->execute();

        $this->assertSame(0, todo::count_records([
            'targetuserid' => $student->id,
            'planningid' => $planning->get('id'),
            'status' => todo::STATUS_PENDING,
        ]));
        $this->assertSame($otherplanningtodos, todo::count_records([
            'targetuserid' => $student->id,
            'planningid' => $otherplanning->get('id'),
            'status' => todo::STATUS_PENDING,
        ]));
        $this->assertSame($otherstudenttodos, todo::count_records([
            'targetuserid' => core_user::get_user_by_username('student2')->id,
            'planningid' => $planning->get('id'),
            'status' => todo::STATUS_PENDING,
        ]));
    }

    /**
     * Test that cleanup still runs and notification is sent when enabled.
     *
     * @return void
     */
    public function test_student_graded_sends_notification_after_cleanup(): void {
        set_config('student_graded_enabled', 1, 'mod_competvet');
        set_config('immediate_email', 1, 'mod_competvet');

        [$student, $planning, $competvet] = $this->get_grade_context();
        $emailsink = $this->redirectEmails();
        $task = $this->create_task($student->id, $planning->get('id'), $competvet->get_course_module_id());
        $task->execute();

        $this->assertSame(0, todo::count_records([
            'targetuserid' => $student->id,
            'planningid' => $planning->get('id'),
            'status' => todo::STATUS_PENDING,
        ]));
        $emails = $emailsink->get_messages();
        $this->assertCount(1, $emails);
        $this->assertSame($student->email, $emails[0]->to);
    }

    /**
     * Get the student, planning, and activity used by the fixture.
     *
     * @return array
     */
    private function get_grade_context(): array {
        $student = core_user::get_user_by_username('student1');
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $planning = planning::get_record(['situationid' => $situation->get('id'), 'session' => '2023']);
        $competvet = competvet::get_from_situation($situation);
        return [$student, $planning, $competvet];
    }

    /**
     * Create a pending todo for the given planning.
     *
     * @param int $studentid
     * @param int $planningid
     * @return todo
     */
    private function create_pending_todo(int $studentid, int $planningid): todo {
        $todo = new todo(0, (object) [
            'userid' => core_user::get_user_by_username('observer1')->id,
            'targetuserid' => $studentid,
            'planningid' => $planningid,
            'status' => todo::STATUS_PENDING,
            'action' => todo::ACTION_EVAL_OBSERVATION_ASKED,
            'data' => '{}',
        ]);
        $todo->save();
        return $todo;
    }

    /**
     * Create the task with the same context as the final grade update path.
     *
     * @param int $studentid
     * @param int $planningid
     * @param int $cmid
     * @return student_graded
     */
    private function create_task(int $studentid, int $planningid, int $cmid): student_graded {
        $task = new student_graded();
        $task->set_custom_data((object) [
            'studentid' => $studentid,
            'planningid' => $planningid,
            'cmid' => $cmid,
        ]);
        return $task;
    }
}
