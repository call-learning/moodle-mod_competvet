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

namespace mod_competvet\external;

use core_external\external_api;
use mod_competvet\local\persistent\planning;
use mod_competvet\local\persistent\situation;
use mod_competvet\tests\test_data_definition;

/**
 * Manage plannings external tests.
 *
 * @package     mod_competvet
 * @copyright   2026 CALL Learning <contact@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\mod_competvet\external\manage_plannings::class)]
final class manage_plannings_test extends \advanced_testcase {
    use test_data_definition;

    /**
     * Setup the test.
     *
     * @return void
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->prepare_scenario('set_1');
        $this->set_current_date();
        $this->setAdminUser();
    }

    /**
     * Test that a batch update skips historical (read-only) plannings and processes the rest.
     *
     * @return void
     */
    public function test_update_batch_delete_skips_historical_plannings(): void {
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $plannings = array_values(planning::get_records(['situationid' => $situation->get('id')]));
        $this->assertCount(3, $plannings);

        // The historical planning is the one whose group is shared by no other planning.
        $firstgroupid = $plannings[0]->get('groupid');
        $historicalplanning = null;
        $otherplannings = [];
        foreach ($plannings as $planning) {
            if ($historicalplanning === null && $planning->get('groupid') !== $firstgroupid) {
                $historicalplanning = $planning;
            } else {
                $otherplannings[] = $planning;
            }
        }
        $this->assertNotNull($historicalplanning);

        // Delete the group to make the planning historical (read-only).
        $group = groups_get_group($historicalplanning->get('groupid'));
        groups_delete_group($group);

        // Flag every planning as deleted in a single batch request.
        $payload = [];
        foreach ($plannings as $planning) {
            $payload[] = [
                'id' => $planning->get('id'),
                'situationid' => $situation->get('id'),
                'groupid' => $planning->get('groupid'),
                'startdate' => gmdate('Y-m-d', $planning->get('startdate')),
                'enddate' => gmdate('Y-m-d', $planning->get('enddate')),
                'session' => $planning->get('session'),
                'deleted' => true,
                'pauses' => [],
            ];
        }

        $result = $this->manage_plannings(['plannings' => $payload]);

        $this->assertTrue($result['result']);
        // The historical planning is preserved...
        $this->assertTrue(planning::record_exists($historicalplanning->get('id')));
        // ...and the other plannings are deleted.
        foreach ($otherplannings as $planning) {
            $this->assertFalse(planning::record_exists($planning->get('id')));
        }
    }

    /**
     * Helper for manage_plannings::update.
     *
     * @param array $args
     * @return array
     */
    protected function manage_plannings(array $args): array {
        $returnvalue = manage_plannings::update($args['plannings']);
        return external_api::clean_returnvalue(manage_plannings::update_returns(), $returnvalue);
    }
}
