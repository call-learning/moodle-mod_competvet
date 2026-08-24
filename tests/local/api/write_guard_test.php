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
use mod_competvet\local\persistent\case_entry;
use mod_competvet\local\persistent\cert_decl;
use mod_competvet\local\persistent\observation;
use mod_competvet\local\persistent\planning;
use mod_competvet\local\persistent\situation;
use mod_competvet\tests\test_data_definition;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for historical planning write guards.
 *
 * Write guard tests for historical plannings.
 *
 * @package     mod_competvet
 * @copyright   2026 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \mod_competvet\local\api\plannings
 * @covers \mod_competvet\local\api\observations
 * @covers \mod_competvet\local\api\certifications
 * @covers \mod_competvet\local\api\cases
 */
final class write_guard_test extends advanced_testcase {
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
     * Test that updating a historical planning is rejected.
     *
     * @return void
     */
    public function test_update_historical_planning_rejected(): void {
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $plannings = planning::get_records(['situationid' => $situation->get('id')]);
        $planning = reset($plannings);
        $planningid = $planning->get('id');
        $groupid = $planning->get('groupid');

        // Delete the group to make the planning historical.
        $group = groups_get_group($groupid);
        groups_delete_group($group);

        // Attempt to update the planning should throw.
        $this->expectException(\moodle_exception::class);
        plannings::update_planning(
            $planningid,
            $situation->get('id'),
            999, // Different group ID.
            gmdate('Y-m-d'),
            gmdate('Y-m-d', strtotime('+1 week')),
            '2023'
        );

        // Planning should still exist unchanged.
        $this->assertTrue(planning::record_exists(['id' => $planningid]));
    }

    /**
     * Test that deleting a historical planning is rejected.
     *
     * @return void
     */
    public function test_delete_historical_planning_rejected(): void {
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $plannings = planning::get_records(['situationid' => $situation->get('id')]);
        $planning = reset($plannings);
        $planningid = $planning->get('id');
        $groupid = $planning->get('groupid');

        // Delete the group.
        $group = groups_get_group($groupid);
        groups_delete_group($group);

        // Attempt to delete should throw.
        $this->expectException(\moodle_exception::class);
        plannings::delete_planning($planningid);

        // Planning should still exist.
        $this->assertTrue(planning::record_exists(['id' => $planningid]));
    }

    /**
     * Test that creating an observation on a historical planning is rejected.
     *
     * @return void
     */
    public function test_create_observation_on_historical_rejected(): void {
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $plannings = planning::get_records(['situationid' => $situation->get('id')]);
        $planning = reset($plannings);
        $planningid = $planning->get('id');
        $groupid = $planning->get('groupid');

        // Delete the group.
        $group = groups_get_group($groupid);
        groups_delete_group($group);

        // Attempt to create observation should throw.
        $this->expectException(\moodle_exception::class);
        observations::create_observation(
            observation::CATEGORY_EVAL_OBSERVATION,
            $planningid,
            1, // Student ID.
            1, // Observer ID.
            'Test context'
        );
    }

    /**
     * Test that editing an observation on a historical planning is rejected.
     *
     * @return void
     */
    public function test_edit_observation_on_historical_rejected(): void {
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $plannings = planning::get_records(['situationid' => $situation->get('id')]);
        $planning = reset($plannings);
        $planningid = $planning->get('id');
        $groupid = $planning->get('groupid');

        // Create an observation first (before group deletion).
        $obsid = observations::create_observation(
            observation::CATEGORY_EVAL_OBSERVATION,
            $planningid,
            1,
            1,
            'Original context'
        );

        // Now delete the group to make the planning historical.
        $group = groups_get_group($groupid);
        groups_delete_group($group);

        // Now try to edit it (should fail because planning is historical).
        $this->expectException(\moodle_exception::class);
        observations::edit_observation($obsid, 'New context');
    }

    /**
     * Test that creating a certification on a historical planning is rejected.
     *
     * @return void
     */
    public function test_create_certification_on_historical_rejected(): void {
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $plannings = planning::get_records(['situationid' => $situation->get('id')]);
        $planning = reset($plannings);
        $planningid = $planning->get('id');
        $groupid = $planning->get('groupid');

        // Delete the group.
        $group = groups_get_group($groupid);
        groups_delete_group($group);

        // Attempt to create certification should throw.
        $this->expectException(\moodle_exception::class);
        certifications::add_cert_declaration(
            1, // Criterion ID.
            1, // Student ID.
            $planningid,
            50, // Level.
            'Comment',
            FORMAT_PLAIN,
            1 // Status.
        );
    }

    /**
     * Test that creating a case on a historical planning is rejected.
     *
     * @return void
     */
    public function test_create_case_on_historical_rejected(): void {
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $plannings = planning::get_records(['situationid' => $situation->get('id')]);
        $planning = reset($plannings);
        $planningid = $planning->get('id');
        $groupid = $planning->get('groupid');

        // Delete the group.
        $group = groups_get_group($groupid);
        groups_delete_group($group);

        // Attempt to create case should throw.
        $this->expectException(\moodle_exception::class);
        cases::create_case($planningid, 1, []);
    }

    /**
     * Test that rejected writes leave existing records unchanged.
     *
     * @return void
     */
    public function test_rejected_writes_preserve_existing_data(): void {
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $plannings = planning::get_records(['situationid' => $situation->get('id')]);
        $planning = reset($plannings);
        $planningid = $planning->get('id');
        $groupid = $planning->get('groupid');

        // Create an observation before group deletion.
        $obsid = observations::create_observation(
            observation::CATEGORY_EVAL_OBSERVATION,
            $planningid,
            1,
            1,
            'Original context'
        );
        $beforecount = observation::count_records(['planningid' => $planningid]);

        // Delete the group to make planning historical.
        $group = groups_get_group($groupid);
        groups_delete_group($group);

        // Try to create another observation (should fail).
        $this->expectException(\moodle_exception::class);
        observations::create_observation(
            observation::CATEGORY_EVAL_OBSERVATION,
            $planningid,
            1,
            1,
            'Should not be created'
        );

        // Count should be unchanged.
        $aftercount = observation::count_records(['planningid' => $planningid]);
        $this->assertEquals($beforecount, $aftercount, 'Observation count should be unchanged after rejected write');
    }
}
