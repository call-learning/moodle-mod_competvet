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

use mod_competvet\task\student_target;
use test_data_definition;
use advanced_testcase;
use DateTime;

/**
 * Tests for CompetVet
 *
 * @package    mod_competvet
 * @category   test
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class student_target_task_test extends advanced_testcase {
    use test_data_definition;

    /**
     * Setup the test
     *
     * @return void
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $competvetgenerator = $generator->get_plugin_generator('mod_competvet');
        $startdate = new DateTime('12 days ago');
        $this->generates_definition(
            $this->{'get_data_definition_set_2'}($startdate->getTimestamp()),
            $generator,
            $competvetgenerator
        );
    }

    /**
     * Test that the student target task sends an email to students who have not met the target.
     * @covers \mod_competvet\task\student_target::execute
     *
     * @return void
     */
    public function test_student_target_task(): void {
        $emailsink = $this->redirectEmails();
        $task = new student_target();
        $task->execute();
        $emails = $emailsink->get_messages();
        $this->assertCount(4, $emails);
        // Run the task again to check if the emails are not sent twice.
        $emailsink = $this->redirectEmails();
        $task->execute();
        $emails = $emailsink->get_messages();
        $this->assertCount(0, $emails);
    }
}
