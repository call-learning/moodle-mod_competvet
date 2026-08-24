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

use advanced_testcase;
use core\event\group_deleted;
use mod_competvet\local\persistent\group_history;
use mod_competvet\local\persistent\planning;
use mod_competvet\local\persistent\situation;
use mod_competvet\tests\test_data_definition;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Group deletion observer tests.
 *
 * @package     mod_competvet
 * @copyright   2026 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(group_deleted_observer::class)]
#[CoversClass(group_history::class)]
final class group_deleted_observer_test extends advanced_testcase {
    use test_data_definition;

    /** @var object The course containing the test scenario. */
    private $course;

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
        global $DB;
        $this->course = $DB->get_record('course', ['shortname' => 'course 1']);
    }

    /**
     * Test that deleting a group with no associated plannings creates no history records.
     *
     * @return void
     */
    public function test_group_deletion_no_affected_plannings(): void {
        $course = $this->getDataGenerator()->create_course();
        $group = $this->getDataGenerator()->create_group(['courseid' => $course->id, 'name' => 'orphan_group']);

        // Directly call the observer method (bypassing event system for reliability).
        $eventdata = [
            'objectid' => $group->id,
            'contextid' => \context_course::instance($course->id)->id,
            'other' => [
                'courseid' => $course->id,
                'groupid' => $group->id,
                'groupname' => $group->name,
            ],
        ];
        $event = group_deleted::create($eventdata);
        $event->add_record_snapshot('groups', $group);
        group_deleted_observer::group_deleted($event);

        $this->assertEquals(0, group_history::count_records());
    }

    /**
     * Test that deleting a group with one planning creates one history record.
     *
     * @return void
     */
    public function test_group_deletion_one_affected_planning(): void {
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $plannings = planning::get_records(['situationid' => $situation->get('id')]);

        // Find a planning that uses group 8.2 (two plannings use it in set_1: SIT1 and SIT3).
        $planning = null;
        foreach ($plannings as $p) {
            $group = groups_get_group($p->get('groupid'));
            if ($group && $group->name === 'group 8.2') {
                $planning = $p;
                break;
            }
        }
        $this->assertNotNull($planning, 'Test requires a planning in group 8.2');

        $groupid = $planning->get('groupid');
        $group = groups_get_group($groupid);
        $groupname = $group->name;

        // Directly call the observer method.
        $eventdata = [
            'objectid' => $groupid,
            'contextid' => \context_course::instance($this->course->id)->id,
            'other' => [
                'courseid' => $this->course->id,
                'groupid' => $groupid,
                'groupname' => $groupname,
            ],
        ];
        $event = group_deleted::create($eventdata);
        $event->add_record_snapshot('groups', $group);
        group_deleted_observer::group_deleted($event);

        // Group 8.2 has 2 plannings in set_1 (SIT1 and SIT3).
        $this->assertEquals(2, group_history::count_records());
        $history = group_history::get_for_planning($planning->get('id'));
        $this->assertNotNull($history);
        $this->assertEquals($planning->get('id'), $history->get('planningid'));
        $this->assertEquals($groupname, $history->get('groupname'));
    }

    /**
     * Test that deleting a group with multiple plannings creates one history record per planning.
     *
     * @return void
     */
    public function test_group_deletion_multiple_affected_plannings(): void {
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $plannings = planning::get_records(['situationid' => $situation->get('id')]);

        // Find all plannings that share the same group (group 8.1).
        $group81plannings = array_filter($plannings, function ($p) {
            $group = groups_get_group($p->get('groupid'));
            return $group && $group->name === 'group 8.1';
        });

        $this->assertGreaterThan(1, count($group81plannings), 'Test requires at least 2 plannings in group 8.1');

        $firstplanning = reset($group81plannings);
        $groupid = $firstplanning->get('groupid');
        $group = groups_get_group($groupid);
        $groupname = $group->name;

        // Directly call the observer method.
        $eventdata = [
            'objectid' => $groupid,
            'contextid' => \context_course::instance($this->course->id)->id,
            'other' => [
                'courseid' => $this->course->id,
                'groupid' => $groupid,
                'groupname' => $groupname,
            ],
        ];
        $event = group_deleted::create($eventdata);
        $event->add_record_snapshot('groups', $group);
        group_deleted_observer::group_deleted($event);

        $this->assertEquals(4, group_history::count_records());

        // Verify each planning has its own history record.
        foreach ($group81plannings as $planning) {
            $history = group_history::get_for_planning($planning->get('id'));
            $this->assertNotNull($history, "Planning {$planning->get('id')} should have a history record");
            $this->assertEquals($groupname, $history->get('groupname'));
        }
    }

    /**
     * Test that repeated group deletion events are idempotent (no duplicate history records).
     *
     * @return void
     */
    public function test_group_deletion_idempotent(): void {
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $plannings = planning::get_records(['situationid' => $situation->get('id')]);

        // Find a planning that uses group 8.2 (only one planning uses it in set_1).
        $planning = null;
        foreach ($plannings as $p) {
            $group = groups_get_group($p->get('groupid'));
            if ($group && $group->name === 'group 8.2') {
                $planning = $p;
                break;
            }
        }
        $this->assertNotNull($planning, 'Test requires a planning in group 8.2');

        $groupid = $planning->get('groupid');
        $group = groups_get_group($groupid);
        $groupname = $group->name;

        $eventdata = [
            'objectid' => $groupid,
            'contextid' => \context_course::instance($this->course->id)->id,
            'other' => [
                'courseid' => $this->course->id,
                'groupid' => $groupid,
                'groupname' => $groupname,
            ],
        ];

        // Call the observer method twice.
        $event1 = group_deleted::create($eventdata);
        $event1->add_record_snapshot('groups', $group);
        group_deleted_observer::group_deleted($event1);
        $event2 = group_deleted::create($eventdata);
        $event2->add_record_snapshot('groups', $group);
        group_deleted_observer::group_deleted($event2);

        // Should still have only one history record per planning (2 total for group 8.2).
        $this->assertEquals(2, group_history::count_records());
        $history = group_history::get_for_planning($planning->get('id'));
        $this->assertNotNull($history);
    }

    /**
     * Test that history records persist after the Moodle group is actually deleted.
     *
     * @return void
     */
    public function test_history_preserved_after_group_deletion(): void {
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $plannings = planning::get_records(['situationid' => $situation->get('id')]);

        // Find a planning that uses group 8.2 (only one planning uses it in set_1).
        $planning = null;
        foreach ($plannings as $p) {
            $group = groups_get_group($p->get('groupid'));
            if ($group && $group->name === 'group 8.2') {
                $planning = $p;
                break;
            }
        }
        $this->assertNotNull($planning, 'Test requires a planning in group 8.2');

        $groupid = $planning->get('groupid');
        $group = groups_get_group($groupid);
        $groupname = $group->name;

        // First call the observer to create history.
        $eventdata = [
            'objectid' => $groupid,
            'contextid' => \context_course::instance($this->course->id)->id,
            'other' => [
                'courseid' => $this->course->id,
                'groupid' => $groupid,
                'groupname' => $groupname,
            ],
        ];
        $event = group_deleted::create($eventdata);
        $event->add_record_snapshot('groups', $group);
        group_deleted_observer::group_deleted($event);

        // Now actually delete the Moodle group.
        groups_delete_group($group);

        // History should still exist (2 records for group 8.2).
        $this->assertEquals(2, group_history::count_records());
        $history = group_history::get_for_planning($planning->get('id'));
        $this->assertNotNull($history);
        $this->assertEquals($groupname, $history->get('groupname'));

        // Planning should still exist.
        $this->assertTrue(planning::record_exists($planning->get('id')));
    }
}
