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

/**
 * TODO describe file clear_pending_todos
 *
 * @package    mod_competvet
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_competvet\local\tasks;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/mod/competvet/tests/test_data_definition.php');

use advanced_testcase;
use mod_competvet\local\persistent\todo;
use mod_competvet\task\clear_pending_todos;
use test_data_definition;

/**
 * Tests for the clear_pending_todos task
 *
 * @package    mod_competvet
 * @category   test
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class clear_pending_todos_task_test extends advanced_testcase {
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
    }

    /**
     * Test that the clear_pending_todos task removes todos older than the configured number of days.
     * @covers \mod_competvet\task\clear_pending_todos::execute
     *
     * @return void
     */
    public function test_clear_pending_todos_task(): void {
        global $DB;

        // Prepare test data.
        $startdate = new \DateTime('last Monday');
        $generator = $this->getDataGenerator();
        $competvetgenerator = $generator->get_plugin_generator('mod_competvet');
        $definition = $this->get_data_definition_set_4($startdate->getTimestamp());
        $this->generates_definition(
            $definition,
            $generator,
            $competvetgenerator
        );

        // Set the configuration to remove todos older than 30 days.
        set_config('clear_pending_todos_days', 30, 'mod_competvet');

        // Execute the task.
        $task = new clear_pending_todos();
        $task->execute();

        // Get records older then 40 days.
        $todos = $DB->get_records_select('competvet_todo', 'timecreated <= :timecreated', ['timecreated' => strtotime('-40 days')]);
        $this->assertFalse(count($todos) > 0);

        // Get records older then 20 days.
        $todos = $DB->get_records_select('competvet_todo', 'timecreated <= :timecreated', ['timecreated' => strtotime('-20 days')]);
        $this->assertTrue(count($todos) > 0);

        // Get records older then 10 days.
        $todos = $DB->get_records_select('competvet_todo', 'timecreated <= :timecreated', ['timecreated' => strtotime('-10 days')]);
        $this->assertTrue(count($todos) > 0);
    }

    /**
     * Test that the clear_pending_todos task removes todos older than a specified date.
     * @covers \mod_competvet\task\clear_pending_todos::execute
     *
     * @return void
     */
    public function test_clear_pending_todos_task_with_date(): void {
        global $DB;

        // Prepare test data.
        $startdate = new \DateTime('last Monday');
        $generator = $this->getDataGenerator();
        $competvetgenerator = $generator->get_plugin_generator('mod_competvet');
        $this->generates_definition(
            $this->get_data_definition_set_4($startdate->getTimestamp()),
            $generator,
            $competvetgenerator
        );

        // Execute the task with a specific date.
        $task = new clear_pending_todos();
        $task->set_timestamp(strtotime('-25 days'));
        $task->execute();

        // Check that the correct todos were removed.
        // Get records older then 40 days.
        $todos = $DB->get_records_select('competvet_todo', 'timecreated <= :timecreated', ['timecreated' => strtotime('-40 days')]);
        $this->assertFalse(count($todos) > 0);

        // Get records older then 20 days.
        $todos = $DB->get_records_select('competvet_todo', 'timecreated <= :timecreated', ['timecreated' => strtotime('-20 days')]);
        $this->assertTrue(count($todos) > 0);

        // Get records older then 10 days.
        $todos = $DB->get_records_select('competvet_todo', 'timecreated <= :timecreated', ['timecreated' => strtotime('-10 days')]);
        $this->assertTrue(count($todos) > 0);
    }
}