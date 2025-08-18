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
// phpcs:ignoreFile

namespace mod_competvet;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/mod/competvet/tests/test_data_definition.php');
require_once($CFG->dirroot . '/mod/competvet/lib.php');

use advanced_testcase;
use core_user;
use mod_competvet\local\api\plannings;
use mod_competvet\local\persistent\situation;
use test_data_definition;

/**
 * Situation cache tests
 *
 * @package     mod_competvet
 * @copyright   2023 CALL Learning <contact@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class situation_cache_test extends advanced_testcase {
    use test_data_definition;

    /**
     * Data definition
     *
     * @param int $startdate
     * @return array $datadefinition
     */
    private function get_dataset(int $startdate): array {
        $oneweek = 60 * 60 * 24 * 7; // 1 week in seconds.
        $onemonth = $oneweek * 4; // 1 month in seconds.
        return [
            'course 1' => [
                'users' => [
                    'student' => ['student1', 'student2'],
                    'observer' => ['observer1', 'observer2'],
                    'teacher' => ['teacher1'],
                    'manager' => ['manager'],
                ],
                'groups' => [
                    'group 8.1' => [
                        'users' => ['student1'],
                    ],
                    'group 8.2' => [
                        'users' => ['student2'],
                    ],
                    'group 8.3' => [
                        'users' => [],
                    ],
                    'group 8.4' => [
                        'users' => [],
                    ],
                ],
                'activities' => [
                    'SIT1' => [
                        'category' => 'Y1',
                        'plannings' => [
                            [
                                'startdate' => $startdate - $oneweek,
                                'enddate' => $startdate + $oneweek,
                                'groupname' => 'group 8.1',
                                'session' => '2023',
                            ],
                            [
                                'startdate' => $startdate - $oneweek,
                                'enddate' => $startdate + $oneweek * 2,
                                'groupname' => 'group 8.2',
                                'session' => '2023',
                            ],
                            [
                                'startdate' => $startdate - $oneweek/2,
                                'enddate' => $startdate + $oneweek,
                                'groupname' => 'group 8.1',
                                'session' => '2023',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
    /**
     * Set up the test
     *
     * @return void
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $competvetgenerator = $generator->get_plugin_generator('mod_competvet');
        $clock = $this->mock_clock_with_frozen();
        $lastmonday = $clock->now();
        $lastmonday = $lastmonday->modify('last monday');
        $this->generates_definition(
            $this->get_dataset(
                $lastmonday->getTimestamp()
            ),
            $generator,
            $competvetgenerator
        );
    }

    /**
     * Test create situation cache
     *
     * @return void
     */
    public function test_create_situation_cache() {
        global $DB;
        $student1 = core_user::get_user_by_username('student1');
        $this->setUser($student1);
        $situations = situation::get_all_situations_id_for($student1->id);
        $this->assertCount(1, $situations);
        $this->setAdminUser();
        $course = $DB->get_record('course', ['shortname' => 'course 1']);
        $this->getDataGenerator()->create_module('competvet', ['course' => $course->id]);
        $this->setUser($student1);
        $situations = situation::get_all_situations_id_for($student1->id);
        $this->assertCount(2, $situations);
    }

    /**
     * Test delete situation cache
     *
     * @covers \mod_competvet\local\persistent\situation::get_all_situations_id_for
     * @return void
     */
    public function test_delete_situation_cache() {
        global $DB;
        $student1 = core_user::get_user_by_username('student1');
        $course = $DB->get_record('course', ['shortname' => 'course 1']);
        $situation = $this->getDataGenerator()->create_module('competvet', ['course' => $course->id]);
        $this->setUser($student1);
        $situations = situation::get_all_situations_id_for($student1->id);
        $this->assertCount(2, $situations);
        $modinfo = get_fast_modinfo($course);
        [$course, $cm] = get_course_and_cm_from_instance($situation->id, 'competvet');
        course_delete_module($cm->id);
        $this->run_all_adhoc_tasks();
        $situations = situation::get_all_situations_id_for($student1->id);
        $this->assertCount(1, $situations);
    }

    /**
     * Test enrol student
     *
     * @covers \mod_competvet\local\persistent\situation::get_all_situations_id_for
     * @return void
     */
    public function test_enrol_student() {
        global $DB;
        $course = $DB->get_record('course', ['shortname' => 'course 1']);
        $studentx = $this->getDataGenerator()->create_and_enrol($course);
        $situations = situation::get_all_situations_id_for($studentx->id);
        $this->assertCount(1, $situations);
    }

    /**
     * Test enrol student
     *
     * @covers \mod_competvet\local\persistent\situation::get_all_situations_id_for
     * @return void
     */
    public function test_unenrol_student() {
        global $DB;
        $course = $DB->get_record('course', ['shortname' => 'course 1']);
        $student1 = core_user::get_user_by_username('student1');
        $situations = situation::get_all_situations_id_for($student1->id);
        $this->assertCount(1, $situations);
        $plugin = enrol_get_plugin('manual');
        $manualenrol = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'manual'], '*', MUST_EXIST);
        $plugin->unenrol_user($manualenrol, $student1->id);
        $situations = situation::get_all_situations_id_for($student1->id);
        $this->assertCount(0, $situations);
    }

    /**
     * Test add a student to a group
     *
     * @covers \mod_competvet\local\persistent\situation::get_all_situations_id_for
     * @return void
     */
    public function test_group_member_added() {
        global $DB;
        $student1 = core_user::get_user_by_username('student1');
        $situations = situation::get_all_situations_id_for($student1->id);
        $this->assertCount(1, $situations);
        $firstsituation = $situations[0];
        $plannings =  plannings::get_plannings_for_situation_id($firstsituation, $student1->id);
        $this->assertCount(2, $plannings); // No planning for this situation yet as student is not in group.
        $group = $DB->get_record('groups', ['name' => 'group 8.2'], '*', MUST_EXIST);
        groups_add_member($group, $student1->id); // Add student to group.
        $situations = situation::get_all_situations_id_for($student1->id);
        $this->assertCount(1, $situations);
        $plannings =  plannings::get_plannings_for_situation_id($firstsituation, $student1->id);
        $this->assertCount(3, $plannings); // We can see all plannings.
    }

    /**
     * Test remove a student from a group
     *
     * @covers \mod_competvet\local\persistent\situation::get_all_situations_id_for
     * @return void
     */
    public function test_group_member_removed() {
        global $DB;
        $student1 = core_user::get_user_by_username('student1');
        $situations = situation::get_all_situations_id_for($student1->id);
        $this->assertCount(1, $situations);
        $firstsituation = $situations[0];
        $plannings =  plannings::get_plannings_for_situation_id($firstsituation, $student1->id);
        $this->assertCount(2, $plannings); // No planning for this situation yet as student is not in group.
        $group = $DB->get_record('groups', ['name' => 'group 8.1'], '*', MUST_EXIST);
        groups_remove_member($group, $student1->id); // Add student to group.
        $situations = situation::get_all_situations_id_for($student1->id);
        $this->assertCount(1, $situations);
        $plannings =  plannings::get_plannings_for_situation_id($firstsituation, $student1->id);
        $this->assertCount(0, $plannings); // We should not see any planning as we are not in a group.
    }

    /**
     * Test assign a student to a role
     *
     * @covers \mod_competvet\local\persistent\situation::get_all_situations_id_for
     * @return void
     */
    public function test_role_assign() {
        global $DB;
        // Prepare a new role with no capability to view competvet.
        $generator = $this->getDataGenerator();
        $testrole = $generator->create_role([
            'shortname' => 'newrole',
            'name' => 'Test role',
            'archetype' => 'student',
        ]);
        $course = $DB->get_record('course', ['shortname' => 'course 1']);
        // Make sure the role has no capability to view competvet.
        assign_capability('mod/competvet:view', CAP_PROHIBIT, $testrole, \context_course::instance($course->id));
        $studentrole = $DB->get_record('role', ['shortname' => 'student'], '*', MUST_EXIST);
        $student = $generator->create_and_enrol(
            $DB->get_record('course', ['shortname' => 'course 1']),
        );
        // Check that the student has a situation.
        $situations = situation::get_all_situations_id_for($student->id);
        $this->assertCount(1, $situations);

        role_assign($testrole, $student->id, \context_course::instance($course->id)->id);
        $situations = situation::get_all_situations_id_for($student->id);
        // The student should not have any situation as the role prevents the student to view competvet.
        $this->assertCount(0, $situations);
    }

    /**
     * Test unassign a student to a role
     *
     * @covers \mod_competvet\local\persistent\situation::get_all_situations_id_for
     * @return void
     */
    public function test_role_unassign() {
        global $DB;
        // Prepare a new role with no capability to view competvet.
        $generator = $this->getDataGenerator();
        $testrole = $generator->create_role([
            'shortname' => 'newrole',
            'name' => 'Test role',
            'archetype' => 'student',
        ]);
        $course = $DB->get_record('course', ['shortname' => 'course 1']);
        // Make sure the role has no capability to view competvet.
        assign_capability('mod/competvet:view', CAP_PROHIBIT, $testrole, \context_course::instance($course->id));
        $student = $generator->create_and_enrol(
            $DB->get_record('course', ['shortname' => 'course 1']),
            'newrole'
        );
        $generator->enrol_user($student->id, $course->id, 'student'); // Make sure the user is enrolled also
        // as a student.

        // Check that the student has no situation (new role prevents this).
        $situations = situation::get_all_situations_id_for($student->id);
        $this->assertCount(0, $situations);

        role_unassign($testrole, $student->id, \context_course::instance($course->id)->id);
        // After unassigning the role, the student should have the situation as the capability to view is allowed.
        $situations = situation::get_all_situations_id_for($student->id);
        $this->assertCount(1, $situations);
    }
}
