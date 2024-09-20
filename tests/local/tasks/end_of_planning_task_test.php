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
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/mod/competvet/tests/test_data_definition.php');

use advanced_testcase;
use core_user;
use DateTime;
use mod_competvet\event\observation_requested;
use mod_competvet\local\persistent\planning;
use mod_competvet\local\persistent\situation;
use mod_competvet\task\end_of_planning;
use test_data_definition;

/**
 * User role test
 *
 * @package     mod_competvet
 * @copyright   2023 CALL Learning <contact@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class end_of_planning_task_test extends advanced_testcase {
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
        $generator = $this->getDataGenerator();
        $competvetgenerator = $generator->get_plugin_generator('mod_competvet');
        $startdate = new DateTime('15 days ago');
        $this->generates_definition(
            $this->{'get_data_definition_set_2'}($startdate->getTimestamp()),
            $generator,
            $competvetgenerator
        );
    }

    /**
     * Test that sending an observation request event will actually create a todo.
     *
     * @return void
     * @covers       \mod_competvet\event\observation_requested::create_from_planning
     */
    public function test_end_of_planning_without_grade() {
        $emailsink = $this->redirectEmails();
        $endofplanningtasks = new end_of_planning();
        $endofplanningtasks->execute();
        $emails = $emailsink->get_messages();
        $this->assertCount(1, $emails);
        $this->assertEquals('[CompetVet] You have students to grade in the rotation SIT1', $emails[0]->subject);
    }
}