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
 * Get student list external tests.
 *
 * @package     mod_competvet
 * @copyright   2026 CALL Learning <contact@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\mod_competvet\external\get_student_list::class)]
final class get_student_list_test extends \advanced_testcase {
    use test_data_definition;

    /**
     * Setup the test.
     *
     * @return void
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->prepare_scenario('set_2');
        $this->set_current_date();
        $this->setAdminUser();
    }

    /**
     * Test get_student_list returns a zero-based list payload.
     */
    public function test_execute_returns_list_shaped_users(): void {
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $plannings = planning::get_records(['situationid' => $situation->get('id')], 'id');
        $planning = reset($plannings);

        $result = $this->get_student_list(['planningid' => $planning->get('id')]);

        $this->assertNotEmpty($result['users']);
        $this->assertIsList($result['users']);
    }

    /**
     * Helper for get_student_list::execute.
     *
     * @param array $args
     * @return array
     */
    protected function get_student_list(array $args): array {
        $params = get_student_list::validate_parameters(get_student_list::execute_parameters(), $args);
        $returnvalue = get_student_list::execute(...array_values($params));
        return external_api::clean_returnvalue(get_student_list::execute_returns(), $returnvalue);
    }
}
