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

namespace mod_competvet\local\observers;
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/mod/competvet/tests/test_data_definition.php');

use advanced_testcase;
use core_user;
use mod_competvet\event\observation_requested;
use mod_competvet\local\api\observations;
use mod_competvet\local\api\plannings;
use mod_competvet\local\api\todos;
use mod_competvet\local\persistent\planning;
use mod_competvet\local\persistent\situation;
use mod_competvet\local\persistent\todo;
use test_data_definition;

/**
 * Observation request API test
 *
 * @package     mod_competvet
 * @copyright   2023 CALL Learning <contact@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class observation_requested_test extends advanced_testcase {
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
     * Test that sending an observation request event will actually create a todo.
     *
     * @return void
     * @covers       \mod_competvet\event\observation_requested::create_from_planning
     */
    public function test_request_observation_trigger_event(): void {
        $student = core_user::get_user_by_username('student1');
        $observer = core_user::get_user_by_username('observer1');
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $plannings = plannings::get_plannings_for_situation_id($situation->get('id'), $student->id);
        $planninginfo = array_shift($plannings);
        $planning = planning::get_record(['id' => $planninginfo['id']]);
        $eventsink = $this->redirectEvents(); // Here call to trigger will never dispatch the event, just create it.
        $event = observation_requested::create_from_planning($planning, 'A context for observation', $observer->id, $student->id);
        $event->trigger();
        $this->assertCount(
            1,
            array_filter(
                $eventsink->get_events(),
                fn($event) => $event->eventname === '\mod_competvet\event\observation_requested'
            )
        );
    }

    /**
     * Test that sending an observation request event will actually create a todo.
     *
     * @return void
     * @covers       \mod_competvet\local\observers\observervation_observer::observation_requested
     */
    public function test_request_observation_create_todo(): void {
        $student = core_user::get_user_by_username('student1');
        $observer = core_user::get_user_by_username('observer1');
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $plannings = plannings::get_plannings_for_situation_id($situation->get('id'), $student->id);
        $planninginfo = array_shift($plannings);
        $planning = planning::get_record(['id' => $planninginfo['id']]);
        $event = observation_requested::create_from_planning($planning, 'A context for observation', $observer->id, $student->id);
        $event->trigger();
        // Check that a todo has been created.
        $this->assertEquals(1, todo::count_records());
        $todo = todo::get_record(['userid' => $observer->id, 'targetuserid' => $student->id, 'planningid' => $planning->get('id')]);
        $this->assertEquals(todo::ACTION_EVAL_OBSERVATION_ASKED, $todo->get('action'));
    }

    /**
     * Test that completing an observation that is marked as todo will complete the todo.
     *
     * @return void
     * @covers       \mod_competvet\local\observers\observervation_observer::observation_requested
     */
    public function test_request_observation_complete_todo_status(): void {
        $student = core_user::get_user_by_username('student1');
        $observer = core_user::get_user_by_username('observer1');
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $plannings = plannings::get_plannings_for_situation_id($situation->get('id'), $student->id);
        $planninginfo = array_shift($plannings);
        $planning = planning::get_record(['id' => $planninginfo['id']]);
        $event = observation_requested::create_from_planning($planning, 'A context for observation', $observer->id, $student->id);
        $event->trigger();
        // Check that a todo has been created.
        $this->assertEquals(1, todo::count_records());
        $todo = todo::get_record(['userid' => $observer->id, 'targetuserid' => $student->id, 'planningid' => $planning->get('id')]);
        $this->assertEquals(todo::STATUS_PENDING, $todo->get('status'));
        // Now act on the todo.
        todos::act_on_todo($todo->get('id'));
        $todo = todo::get_record(['userid' => $observer->id, 'targetuserid' => $student->id, 'planningid' => $planning->get('id')]);
        $this->assertEquals(todo::STATUS_PENDING, $todo->get('status'));
        $observationid = json_decode($todo->get('data'))->observationid;

        // Now complete the observation.
        observations::edit_observation($observationid);
        $todo->read();
        $this->assertEquals(todo::STATUS_DONE, $todo->get('status'));
    }


}
