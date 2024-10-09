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
namespace mod_competvet\local\api;
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/mod/competvet/tests/test_data_definition.php');

use advanced_testcase;
use core_user;
use mod_competvet\event\observation_requested;
use mod_competvet\local\persistent\planning;
use mod_competvet\local\persistent\situation;
use test_data_definition;

/**
 * Todo API test
 *
 * @package     mod_competvet
 * @copyright   2023 CALL Learning <contact@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class todo_test extends advanced_testcase {
    use test_data_definition;

    /**
     * Setup the test
     *
     * @return void
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser(); // Needed for report builder to work.
        $this->prepare_scenario('set_2');
    }

    /**
     * Test get_todos_for_user
     *
     * @return void
     * @covers \mod_competvet\local\api\todos::get_todos_for_user
     */
    public function test_get_todos_for_user(): void {
        $student = core_user::get_user_by_username('student1');
        $observer = core_user::get_user_by_username('observer1');
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $plannings = plannings::get_plannings_for_situation_id($situation->get('id'), $student->id);
        $planninginfo = array_shift($plannings);
        $planning = planning::get_record(['id' => $planninginfo['id']]);
        $event = observation_requested::create_from_planning($planning, 'A context for observation', $observer->id, $student->id);
        $event->trigger();
        $todos = todos::get_todos_for_user($observer->id);

        // Assertions.
        $this->assertNotEmpty($todos, 'Todo list for user is empty.');
        foreach ($todos as $todo) {
            $this->assertEquals($observer->id, $todo['user']['id'], 'Todo user id does not match.');
        }
    }

    /**
     * Test get_todos_for_target_user
     *
     * @return void
     * @covers \mod_competvet\local\api\todos::get_todos_for_target_user
     */
    public function test_get_todos_for_target_user(): void {
        $student = core_user::get_user_by_username('student1');
        $observer = core_user::get_user_by_username('observer1');
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $plannings = plannings::get_plannings_for_situation_id($situation->get('id'), $student->id);
        $planninginfo = array_shift($plannings);
        $planning = planning::get_record(['id' => $planninginfo['id']]);
        $event = observation_requested::create_from_planning($planning, 'A context for observation', $observer->id, $student->id);
        $event->trigger();
        $todos = todos::get_todos_for_target_user($student->id);

        // Assertions.
        $this->assertNotEmpty($todos, 'Todo list for target user is empty.');
        foreach ($todos as $todo) {
            $this->assertEquals($student->id, $todo['targetuser']['id'], 'Todo target user id does not match.');
        }
    }
}
