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
namespace local\api\observers;
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/mod/competvet/tests/test_data_definition.php');

use advanced_testcase;
use core_user;
use mod_competvet\event\cert_validation_requested;
use mod_competvet\event\observation_requested;
use mod_competvet\local\api\certifications;
use mod_competvet\local\api\observations;
use mod_competvet\local\api\plannings;
use mod_competvet\local\persistent\cert_decl;
use mod_competvet\local\persistent\cert_decl_asso;
use mod_competvet\local\persistent\criterion;
use mod_competvet\local\persistent\grid;
use mod_competvet\local\persistent\observation;
use mod_competvet\local\persistent\observation_comment;
use mod_competvet\local\persistent\planning;
use mod_competvet\local\persistent\situation;
use mod_competvet\local\persistent\todo;
use test_data_definition;

/**
 * Cert request API test
 *
 * @package     mod_competvet
 * @copyright   2023 CALL Learning <contact@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cert_requested_test extends advanced_testcase {
    use test_data_definition;

    /**
     * @var int $declid
     */
    protected $declid;

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
        $student = core_user::get_user_by_username('student1');
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $plannings = plannings::get_plannings_for_situation_id($situation->get('id'), $student->id);
        $planninginfo = array_shift($plannings);
        $grid = grid::get_record(['type' => grid::COMPETVET_CRITERIA_CERTIFICATION]);
        $criterion = criterion::get_record(['idnumber' => 'CERT1', 'gridid' => $grid->get('id')]);
        // Act: Call the method under test.
        $this->declid = certifications::add_cert_declaration(
            $criterion->get('id'),
            $student->id,
            $planninginfo['id'],
            5,
            'A comment',
            FORMAT_PLAIN,
            cert_decl::STATUS_DECL_SEENDONE
        );
    }

    /**
     * Test that sending an certification request event.
     *
     * @return void
     * @covers       \mod_competvet\event\cert_validation_requested::create_from_decl_and_supervisor
     */
    public function test_request_cert_validation_trigger_event() {
        $student = core_user::get_user_by_username('student1');
        $observer = core_user::get_user_by_username('observer1');
        $eventsink = $this->redirectEvents(); // Here call to trigger will never dispatch the event, just create it.
        $event = cert_validation_requested::create_from_decl_and_supervisor($this->declid, $observer->id, $student->id);
        $event->trigger();
        $this->assertCount(
            1,
            array_filter(
                $eventsink->get_events(),
                fn($event) => $event->eventname === '\mod_competvet\event\cert_validation_requested'
            )
        );
        // The observer should not have run at this stage so the association is not created.
        $this->assertFalse(cert_decl_asso::get_record(['declid' => $this->declid, 'supervisorid' => $observer->id]));
    }

    /**
     * Test that sending an certification request event will actually create a todo.
     *
     * @return void
     * @covers       \mod_competvet\local\observers\cert_observer::cert_validation_requested
     */
    public function test_request_cert_validation_create_todo() {
        $student = core_user::get_user_by_username('student1');
        $observer = core_user::get_user_by_username('observer1');
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $plannings = plannings::get_plannings_for_situation_id($situation->get('id'), $student->id);
        $planninginfo = array_shift($plannings);
        $event = cert_validation_requested::create_from_decl_and_supervisor($this->declid, $observer->id, $student->id);
        $event->trigger();
        // Check that a todo has been created.
        $this->assertEquals(1, todo::count_records());
        $todo = todo::get_record(['userid' => $observer->id, 'targetuserid' => $student->id, 'planningid' => $planninginfo['id']]);
        $this->assertEquals(todo::ACTION_EVAL_CERTIFICATION_VALIDATION_ASKED, $todo->get('action'));
        $this->assertNotEmpty(cert_decl_asso::get_record(['declid' => $this->declid, 'supervisorid' => $observer->id]));
    }
}
