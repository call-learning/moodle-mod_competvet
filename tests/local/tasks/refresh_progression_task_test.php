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

use core_user;
use mod_competvet\local\api\plannings;
use mod_competvet\local\api\progression;
use mod_competvet\local\persistent\planning;
use mod_competvet\local\persistent\situation;
use mod_competvet\task\refresh_progression;
use mod_competvet\tests\test_data_definition;

/**
 * Tests for the scheduled progression refresh.
 *
 * @package   mod_competvet
 * @copyright 2026 CALL Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
/**
 * Tests for refreshing progression.
 *
 * @covers \mod_competvet\task\refresh_progression
 */
final class refresh_progression_task_test extends \advanced_testcase {
    use test_data_definition;

    public function test_refresh_populates_historical_contexts(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->prepare_scenario('set_3');
        $this->set_current_date();

        $situation = situation::get_record(['shortname' => 'SIT1']);
        $student = core_user::get_user_by_username('student1');
        $planningdata = plannings::get_plannings_for_situation_id($situation->get('id'), $student->id);
        $planning = planning::get_record(['id' => reset($planningdata)['id']]);
        $DB->delete_records('competvet_progression');

        $task = new refresh_progression();
        $task->execute();

        $results = $DB->get_records('competvet_progression', [
            'planningid' => $planning->get('id'),
            'studentid' => $student->id,
        ]);
        $this->assertNotEmpty($results);
        $this->assertSame(
            count($results),
            $DB->count_records_sql(
                'SELECT COUNT(DISTINCT criterionid) FROM {competvet_progression}
                  WHERE planningid = :planningid AND studentid = :studentid',
                ['planningid' => $planning->get('id'), 'studentid' => $student->id]
            )
        );
        $this->assertNotEmpty(progression::get_progression_summary($planning->get('id'), $student->id));

        $timecalculated = $DB->get_field_sql(
            'SELECT MAX(timecalculated) FROM {competvet_progression}
              WHERE planningid = :planningid AND studentid = :studentid',
            ['planningid' => $planning->get('id'), 'studentid' => $student->id]
        );
        $task->execute();
        $this->assertSame(
            $timecalculated,
            $DB->get_field_sql(
                'SELECT MAX(timecalculated) FROM {competvet_progression}
                  WHERE planningid = :planningid AND studentid = :studentid',
                ['planningid' => $planning->get('id'), 'studentid' => $student->id]
            )
        );
    }
}
