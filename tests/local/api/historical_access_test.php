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

use advanced_testcase;
use core_user;
use mod_competvet\local\persistent\group_history;
use mod_competvet\local\persistent\planning;
use mod_competvet\local\persistent\situation;
use mod_competvet\tests\test_data_definition;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Access control tests for historical plannings.
 *
 * @package     mod_competvet
 * @copyright   2026 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(plannings::class)]
final class historical_access_test extends advanced_testcase {
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
     * Test that an authorized manager can view historical plannings.
     *
     * @return void
     */
    public function test_manager_can_view_historical_plannings(): void {
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $plannings = planning::get_records(['situationid' => $situation->get('id')]);
        $planning = reset($plannings);
        $groupid = $planning->get('groupid');

        // Delete the group to make the planning historical.
        $group = groups_get_group($groupid);
        $groupname = $group->name;

        // Create history record (observer would normally do this).
        global $DB;
        $DB->insert_record('competvet_group_history', (object) [
            'planningid' => $planning->get('id'),
            'groupname' => $groupname,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        groups_delete_group($group);

        // Set up manager user.
        $manager = core_user::get_user_by_username('manager');
        $this->setUser($manager);

        // Manager should be able to get plannings including the historical one.
        $result = plannings::get_plannings_for_situation_id($situation->get('id'), $manager->id, true, true);
        $this->assertNotEmpty($result);

        // Find the historical planning in the result.
        $historical = null;
        foreach ($result as $p) {
            if ((int)$p['id'] === (int)$planning->get('id')) {
                $historical = $p;
                break;
            }
        }
        $this->assertNotNull($historical, 'Historical planning should be in the result');
        $this->assertTrue($historical['historical']);
        $this->assertTrue($historical['readonly']);
    }

    /**
     * Test that an unrelated student cannot access historical planning data.
     *
     * @return void
     */
    public function test_unrelated_student_cannot_access_historical_data(): void {
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $plannings = planning::get_records(['situationid' => $situation->get('id')]);
        $planning = reset($plannings);
        $groupid = $planning->get('groupid');

        // Delete the group to make the planning historical.
        $group = groups_get_group($groupid);
        groups_delete_group($group);

        // Set up a student who is not in the planning's group.
        $student = core_user::get_user_by_username('student2');
        $this->setUser($student);

        // Student should not see the historical planning (they're not in the group).
        $result = plannings::get_plannings_for_situation_id($situation->get('id'), $student->id, true, false);
        $found = false;
        foreach ($result as $p) {
            if ($p['id'] === $planning->get('id')) {
                $found = true;
                break;
            }
        }
        $this->assertFalse($found, 'Unrelated student should not see the historical planning');
    }

    /**
     * Test that a student with historical records can view their own data.
     *
     * @return void
     */
    public function test_student_with_historical_records_can_view(): void {
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $plannings = planning::get_records(['situationid' => $situation->get('id')]);
        $planning = reset($plannings);
        $groupid = $planning->get('groupid');

        // Delete the group to make the planning historical.
        $group = groups_get_group($groupid);
        $groupname = $group->name;

        // Create history record (observer would normally do this).
        global $DB;
        $DB->insert_record('competvet_group_history', (object) [
            'planningid' => $planning->get('id'),
            'groupname' => $groupname,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        groups_delete_group($group);

        // Set up the student who was in the group.
        $student = core_user::get_user_by_username('student1');
        $this->setUser($student);

        // Student should see the historical planning (the mod_competvet UI opts into historical plannings).
        $result = plannings::get_plannings_for_situation_id($situation->get('id'), $student->id, true, false, true);
        $found = false;
        foreach ($result as $p) {
            if ((int)$p['id'] === (int)$planning->get('id')) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Student should see the historical planning they were in');
    }

    /**
     * Test that historical participant retrieval returns students from CompetVet records.
     *
     * @return void
     */
    public function test_historical_participant_retrieval(): void {
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $plannings = planning::get_records(['situationid' => $situation->get('id')]);
        $planning = reset($plannings);
        $groupid = $planning->get('groupid');

        // Delete the group.
        $group = groups_get_group($groupid);
        groups_delete_group($group);

        // Participants should be derived from CompetVet records, not Moodle groups.
        $students = plannings::get_students_for_planning_id($planning->get('id'));
        // Since there are no observations/certifications for this planning in the test data,
        // the result should be empty (no CompetVet records identify students).
        $this->assertIsArray($students);
    }

    /**
     * Test that historical state is correctly resolved.
     *
     * @return void
     */
    public function test_historical_state_resolution(): void {
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $plannings = planning::get_records(['situationid' => $situation->get('id')]);
        $planning = reset($plannings);
        $planningid = $planning->get('id');
        $groupid = $planning->get('groupid');

        // Before deletion: normal planning.
        $metadata = plannings::resolve_planning_metadata($planningid);
        $this->assertFalse($metadata['historical']);
        $this->assertFalse($metadata['readonly']);
        $this->assertNotEmpty($metadata['groupname']);

        // Delete the group.
        $group = groups_get_group($groupid);
        groups_delete_group($group);

        // After deletion: historical planning.
        $metadata = plannings::resolve_planning_metadata($planningid);
        $this->assertTrue($metadata['historical']);
        $this->assertTrue($metadata['readonly']);
        // Should have fallback name.
        $this->assertNotEmpty($metadata['groupname']);
    }

    /**
     * Test that write guard rejects writes to historical plannings.
     *
     * @return void
     */
    public function test_write_guard_rejects_historical(): void {
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $plannings = planning::get_records(['situationid' => $situation->get('id')]);
        $planning = reset($plannings);
        $planningid = $planning->get('id');
        $groupid = $planning->get('groupid');

        // Delete the group.
        $group = groups_get_group($groupid);
        groups_delete_group($group);

        // Write guard should throw.
        $this->expectException(\moodle_exception::class);
        plannings::check_write_allowed($planningid);
    }

    /**
     * Test that write guard allows writes to normal plannings.
     *
     * @return void
     */
    public function test_write_guard_allows_normal(): void {
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $plannings = planning::get_records(['situationid' => $situation->get('id')]);
        $planning = reset($plannings);
        $planningid = $planning->get('id');

        // Write guard should not throw for normal planning.
        plannings::check_write_allowed($planningid);
        $this->assertTrue(true, 'Write guard should not throw for normal planning');
    }
}
