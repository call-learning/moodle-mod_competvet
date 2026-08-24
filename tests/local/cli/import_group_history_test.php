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

namespace mod_competvet\local\cli;

use advanced_testcase;
use mod_competvet\local\api\plannings;
use mod_competvet\local\persistent\group_history;
use mod_competvet\local\persistent\planning;
use mod_competvet\local\persistent\situation;
use mod_competvet\tests\test_data_definition;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * CLI import command tests.
 *
 * @package     mod_competvet
 * @copyright   2026 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
/**
 * Tests for importing group history.
 *
 * @covers \mod_competvet\local\api\plannings
 */
final class import_group_history_test extends advanced_testcase {
    use test_data_definition;

    /**
     * Set up the test.
     *
     * @return void
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->prepare_scenario('set_1');
        $this->set_current_date();
    }

    /**
     * Test that import creates history for valid planning.
     *
     * @return void
     */
    public function test_import_valid_metadata(): void {
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $plannings = planning::get_records(['situationid' => $situation->get('id')]);
        $planning = reset($plannings);
        $planningid = $planning->get('id');

        $beforecount = group_history::count_records();

        // Import history.
        $results = plannings::import_group_history([[$planningid, 'Test Group Name']]);

        $aftercount = group_history::count_records();
        $this->assertEquals($beforecount + 1, $aftercount);

        $this->assertCount(1, $results);
        $this->assertEquals('created', $results[0]->status);
        $this->assertEquals('Test Group Name', $results[0]->groupname);

        $history = group_history::get_for_planning($planningid);
        $this->assertNotNull($history);
        $this->assertEquals('Test Group Name', $history->get('groupname'));
    }

    /**
     * Test that import reports unknown planning ID.
     *
     * @return void
     */
    public function test_import_unknown_planning_id(): void {
        $results = plannings::import_group_history([[99999, 'Test Group']]);
        $this->assertCount(1, $results);
        $this->assertEquals('error', $results[0]->status);
        $this->assertStringContainsString('does not exist', $results[0]->message);
    }

    /**
     * Test that import is idempotent (duplicates reported).
     *
     * @return void
     */
    public function test_import_duplicate_reports(): void {
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $plannings = planning::get_records(['situationid' => $situation->get('id')]);
        $planning = reset($plannings);
        $planningid = $planning->get('id');

        // Import once.
        plannings::import_group_history([[$planningid, 'Test Group']]);

        // Import again - should report duplicate.
        $results = plannings::import_group_history([[$planningid, 'Test Group']]);
        $this->assertCount(1, $results);
        $this->assertEquals('duplicate', $results[0]->status);
        $this->assertStringContainsString('already exists', $results[0]->message);

        // Count should still be 1.
        $this->assertEquals(1, group_history::count_records());
    }

    /**
     * Test that import does not create Moodle groups or memberships.
     *
     * @return void
     */
    public function test_import_does_not_create_groups(): void {
        global $DB;
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $plannings = planning::get_records(['situationid' => $situation->get('id')]);
        $planning = reset($plannings);
        $planningid = $planning->get('id');

        // Get the course ID from the planning's situation.
        $competvet = \mod_competvet\competvet::get_from_situation($situation);
        $courseid = $competvet->get_course_module()->course;

        $beforecount = $DB->count_records('groups', ['courseid' => $courseid]);

        plannings::import_group_history([[$planningid, 'Test Group']]);

        $aftercount = $DB->count_records('groups', ['courseid' => $courseid]);
        $this->assertEquals($beforecount, $aftercount, 'Import should not create Moodle groups');
    }

    /**
     * Test dry-run mode.
     *
     * @return void
     */
    public function test_dry_run_mode(): void {
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $plannings = planning::get_records(['situationid' => $situation->get('id')]);
        $planning = reset($plannings);
        $planningid = $planning->get('id');

        $beforecount = group_history::count_records();

        $results = plannings::import_group_history([[$planningid, 'Test Group']], true);
        $this->assertCount(1, $results);
        $this->assertEquals('dryrun', $results[0]->status);
        $this->assertStringContainsString('Would create', $results[0]->message);

        // Count should be unchanged.
        $aftercount = group_history::count_records();
        $this->assertEquals($beforecount, $aftercount);
    }
}
