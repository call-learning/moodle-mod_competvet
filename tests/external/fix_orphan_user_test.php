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

use advanced_testcase;
use core_external\external_api;
use core_user;
use mod_competvet\local\persistent\observation;
use mod_competvet\local\persistent\planning;
use mod_competvet\local\persistent\situation;
use mod_competvet\tests\test_data_definition;

/**
 * Tests for the fix_orphan_user external function.
 *
 * @package     mod_competvet
 * @category    test
 * @copyright   2026 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\mod_competvet\external\fix_orphan_user::class)]
final class fix_orphan_user_test extends advanced_testcase {
    use test_data_definition;

    /**
     * Set up the test fixture.
     *
     * @return void
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->prepare_scenario('set_1');
    }

    /**
     * A manager can re-add an orphaned student to the original planning group.
     *
     * @return void
     */
    public function test_execute_add_as_manager(): void {
        $planning = $this->get_sit1_planning(0); // Group of student1.
        $student2 = core_user::get_user_by_username('student2');
        $manager = core_user::get_user_by_username('manager');
        $this->setUser($manager);
        $this->create_orphan_observation($planning, $student2->id, $student2->id);

        $result = $this->execute([
            'userid' => $student2->id,
            'groupid' => $planning->get('groupid'),
            'planningid' => $planning->get('id'),
            'oldplanningid' => $planning->get('id'),
            'action' => 'orphanfix:add',
        ]);

        $this->assertNotEmpty($result['result']);
        $this->assertTrue(groups_is_member($planning->get('groupid'), $student2->id));
    }

    /**
     * A manager can move an orphaned student's records to a same-week planning.
     *
     * @return void
     */
    public function test_execute_move_as_manager(): void {
        $planning = $this->get_sit1_planning(0); // Group of student1.
        $otherplanning = $this->get_sit1_planning(1); // Group of student2.
        $group2 = $otherplanning->get('groupid');
        $student2 = core_user::get_user_by_username('student2');
        $manager = core_user::get_user_by_username('manager');
        $this->setUser($manager);
        $this->create_orphan_observation($planning, $student2->id, $student2->id);

        $target = new planning(0, (object) [
            'situationid' => $planning->get('situationid'),
            'groupid' => $group2,
            'startdate' => $planning->get('startdate'),
            'enddate' => $planning->get('enddate'),
            'session' => '2023',
        ]);
        $target->create();

        $result = $this->execute([
            'userid' => $student2->id,
            'groupid' => $group2,
            'planningid' => $target->get('id'),
            'oldplanningid' => $planning->get('id'),
            'action' => 'orphanfix:move',
        ]);

        $this->assertNotEmpty($result['result']);
        $this->assertSame(0, observation::count_records(['planningid' => $planning->get('id'), 'studentid' => $student2->id]));
        $this->assertSame(1, observation::count_records(['planningid' => $target->get('id'), 'studentid' => $student2->id]));
    }

    /**
     * A user without the required capabilities cannot fix an orphaned student.
     *
     * @return void
     */
    public function test_execute_without_permission(): void {
        $planning = $this->get_sit1_planning(0);
        $student2 = core_user::get_user_by_username('student2');
        $student1 = core_user::get_user_by_username('student1');
        $this->setUser($student1);
        $this->create_orphan_observation($planning, $student2->id, $student2->id);

        try {
            $this->execute([
                'userid' => $student2->id,
                'groupid' => $planning->get('groupid'),
                'planningid' => $planning->get('id'),
                'oldplanningid' => $planning->get('id'),
                'action' => 'orphanfix:add',
            ]);
            $this->fail('Expected a moodle_exception for a user without permission.');
        } catch (\moodle_exception $e) {
            $this->assertSame('nopermission', $e->errorcode);
        }
    }

    /**
     * An unknown old planning id is rejected.
     *
     * @return void
     */
    public function test_execute_invalid_oldplanningid(): void {
        $planning = $this->get_sit1_planning(0);
        $student2 = core_user::get_user_by_username('student2');
        $manager = core_user::get_user_by_username('manager');
        $this->setUser($manager);

        try {
            $this->execute([
                'userid' => $student2->id,
                'groupid' => $planning->get('groupid'),
                'planningid' => $planning->get('id'),
                'oldplanningid' => 99999,
                'action' => 'orphanfix:add',
            ]);
            $this->fail('Expected a moodle_exception for an unknown old planning id.');
        } catch (\moodle_exception $e) {
            $this->assertSame('invaliddata', $e->errorcode);
        }
    }

    /**
     * Helper for fix_orphan_user::execute.
     *
     * @param array $args
     * @return array
     */
    protected function execute(array $args): array {
        $params = fix_orphan_user::validate_parameters(fix_orphan_user::execute_parameters(), $args);
        $returnvalue = fix_orphan_user::execute($params);
        return external_api::clean_returnvalue(fix_orphan_user::execute_returns(), $returnvalue);
    }

    /**
     * Get a SIT1 planning by its week offset (0 = base week, 1 = next week).
     *
     * @param int $weekoffset
     * @return planning
     */
    private function get_sit1_planning(int $weekoffset): planning {
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $oneweek = 60 * 60 * 24 * 7;
        $startdate = planning::round_start_date(self::get_start_date()->getTimestamp() + ($weekoffset * $oneweek));
        return planning::get_record([
            'situationid' => $situation->get('id'),
            'session' => '2023',
            'startdate' => $startdate,
        ]);
    }

    /**
     * Create an observation record directly on a planning, bypassing the write guard.
     *
     * @param planning $planning
     * @param int $studentid
     * @param int $observerid
     * @return void
     */
    private function create_orphan_observation(planning $planning, int $studentid, int $observerid): void {
        $observation = new observation(0);
        $observation->set('planningid', $planning->get('id'));
        $observation->set('studentid', $studentid);
        $observation->set('observerid', $observerid);
        $observation->set('status', observation::STATUS_NOTSTARTED);
        $observation->set('category', observation::CATEGORY_EVAL_OBSERVATION);
        $observation->create();
    }
}
