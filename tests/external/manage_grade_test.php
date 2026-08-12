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

namespace mod_competvet\external;

use advanced_testcase;
use core_external\external_api;
use core_user;
use mod_competvet\competvet;
use mod_competvet\local\persistent\planning;
use mod_competvet\local\persistent\situation;
use mod_competvet\tests\test_data_definition;

/**
 * Tests for the manage_grade external function.
 *
 * @package    mod_competvet
 * @category   test
 * @copyright 2026 CALL Learning <contact@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
/**
 * Tests for grade management.
 *
 * @covers \mod_competvet\external\manage_grade
 */
final class manage_grade_test extends advanced_testcase {
    use test_data_definition;

    /**
     * Set up the test fixture.
     *
     * @return void
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->prepare_scenario('set_5');
    }

    /**
     * Test that a successful grade update queues the post-grade task.
     *
     * @return void
     */
    public function test_update_queues_student_graded_task(): void {
        global $DB;

        $this->setAdminUser();
        [$student, $planning, $competvet] = $this->get_grade_context();
        $result = $this->update([
            'userid' => $student->id,
            'cmid' => $competvet->get_course_module_id(),
            'planningid' => $planning->get('id'),
            'grade' => '10',
        ]);

        $this->assertTrue($result['result']);
        $tasks = $DB->get_records('task_adhoc', ['classname' => '\mod_competvet\task\student_graded']);
        $this->assertCount(1, $tasks);
        $taskdata = json_decode(reset($tasks)->customdata);
        $this->assertEquals($student->id, $taskdata->studentid);
        $this->assertEquals($planning->get('id'), $taskdata->planningid);
    }

    /**
     * Test that a grade update without permission does not queue the task.
     *
     * @return void
     */
    public function test_update_without_permission_does_not_queue_student_graded_task(): void {
        global $DB;

        $student = core_user::get_user_by_username('student1');
        $this->setUser($student);
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $planning = planning::get_record(['situationid' => $situation->get('id'), 'session' => '2023']);
        $competvet = competvet::get_from_situation($situation);
        $result = $this->update([
            'userid' => $student->id,
            'cmid' => $competvet->get_course_module_id(),
            'planningid' => $planning->get('id'),
            'grade' => '10',
        ]);

        $this->assertFalse($result['result']);
        $this->assertCount(0, $DB->get_records('task_adhoc', ['classname' => '\mod_competvet\task\student_graded']));
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
     * Helper for manage_grade::update.
     *
     * @param array $args
     * @return array
     */
    protected function update(array $args): array {
        $params = manage_grade::validate_parameters(manage_grade::update_parameters(), $args);
        $returnvalue = manage_grade::update(...array_values($params));
        return external_api::clean_returnvalue(manage_grade::update_returns(), $returnvalue);
    }
}
