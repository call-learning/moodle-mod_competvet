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
 * CLI detection command tests.
 *
 * @package     mod_competvet
 * @copyright   2026 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
/**
 * Tests for detecting missing groups.
 *
 * @covers \mod_competvet\local\api\plannings
 */
final class detect_missing_groups_test extends advanced_testcase {
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
     * Test that detection reports no missing groups when all groups exist.
     *
     * @return void
     */
    public function test_detection_no_missing_groups(): void {
        $missing = plannings::detect_missing_groups();
        $this->assertEmpty($missing, 'No plannings should have missing groups when all groups exist.');
    }

    /**
     * Test that detection reports missing groups.
     *
     * @return void
     */
    public function test_detection_reports_missing_groups(): void {
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $plannings = planning::get_records(['situationid' => $situation->get('id')]);
        $planning = reset($plannings);
        $planningid = $planning->get('id');
        $groupid = $planning->get('groupid');

        // Delete the group.
        $group = groups_get_group($groupid);
        groups_delete_group($group);

        // Filter by the specific planning to get a predictable result.
        $missing = plannings::detect_missing_groups(null, $planningid);
        $this->assertCount(1, $missing, 'Should find exactly one planning with missing group when filtered by planning ID.');
        $this->assertEquals($planningid, $missing[0]->planningid);
    }

    /**
     * Test that detection reports history presence.
     *
     * @return void
     */
    public function test_detection_reports_history_presence(): void {
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $plannings = planning::get_records(['situationid' => $situation->get('id')]);
        $planning = reset($plannings);
        $groupid = $planning->get('groupid');

        // Delete the group.
        $group = groups_get_group($groupid);
        groups_delete_group($group);

        // Add history record.
        $history = new group_history(0, (object) [
            'planningid' => $planning->get('id'),
            'groupname' => 'Old Group Name',
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
        $history->create();

        $missing = plannings::detect_missing_groups();
        $this->assertNotEmpty($missing);
        $this->assertTrue($missing[0]->history_present, 'History should be present.');
        $this->assertEquals('Old Group Name', $missing[0]->history_name);
    }

    /**
     * Test that detection with no history shows NO.
     *
     * @return void
     */
    public function test_detection_no_history_shows_no(): void {
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $plannings = planning::get_records(['situationid' => $situation->get('id')]);
        $planning = reset($plannings);
        $groupid = $planning->get('groupid');

        // Delete the group without adding history.
        $group = groups_get_group($groupid);
        groups_delete_group($group);

        $missing = plannings::detect_missing_groups();
        $this->assertNotEmpty($missing);
        $this->assertFalse($missing[0]->history_present, 'History should not be present.');
    }

    /**
     * Test that detection does not modify any data.
     *
     * @return void
     */
    public function test_detection_performs_no_writes(): void {
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $plannings = planning::get_records(['situationid' => $situation->get('id')]);
        $planning = reset($plannings);
        $groupid = $planning->get('groupid');

        $beforecount = group_history::count_records();

        // Delete the group.
        $group = groups_get_group($groupid);
        groups_delete_group($group);

        // Run detection.
        plannings::detect_missing_groups();

        // Count should be unchanged.
        $aftercount = group_history::count_records();
        $this->assertEquals($beforecount, $aftercount, 'Detection should not create history records.');
    }

    /**
     * Test that detection filters by situation ID.
     *
     * @return void
     */
    public function test_detection_filters_by_situation(): void {
        $situation1 = situation::get_record(['shortname' => 'SIT1']);
        $situation3 = situation::get_record(['shortname' => 'SIT3']);

        // Delete group from first planning in SIT1.
        $planning1 = planning::get_records(['situationid' => $situation1->get('id')]);
        $firstplanning1 = reset($planning1);
        $group1 = groups_get_group($firstplanning1->get('groupid'));
        groups_delete_group($group1);

        // Delete group from first planning in SIT3.
        $planning3 = planning::get_records(['situationid' => $situation3->get('id')]);
        $firstplanning3 = reset($planning3);
        $group3 = groups_get_group($firstplanning3->get('groupid'));
        groups_delete_group($group3);

        // Filter by SIT1 should only return SIT1 planning.
        $missing = plannings::detect_missing_groups($situation1->get('id'));
        $this->assertNotEmpty($missing);
        foreach ($missing as $m) {
            $this->assertEquals($situation1->get('id'), $m->situationid);
        }

        // Filter by SIT3 should only return SIT3 planning.
        $missing = plannings::detect_missing_groups($situation3->get('id'));
        $this->assertNotEmpty($missing);
        foreach ($missing as $m) {
            $this->assertEquals($situation3->get('id'), $m->situationid);
        }
    }

    /**
     * Test that detection filters by planning ID.
     *
     * @return void
     */
    public function test_detection_filters_by_planning(): void {
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $plannings = planning::get_records(['situationid' => $situation->get('id')]);
        $planning = reset($plannings);
        $groupid = $planning->get('groupid');

        // Delete the group.
        $group = groups_get_group($groupid);
        groups_delete_group($group);

        // Filter by planning ID should return only that planning.
        $missing = plannings::detect_missing_groups(null, $planning->get('id'));
        $this->assertCount(1, $missing);
        $this->assertEquals($planning->get('id'), $missing[0]->planningid);
    }
}
