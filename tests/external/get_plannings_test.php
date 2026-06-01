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
use mod_competvet\competvet;
use mod_competvet\local\persistent\situation;
use mod_competvet\tests\test_data_definition;

/**
 * Get plannings external tests.
 *
 * @package     mod_competvet
 * @copyright   2026 CALL Learning <contact@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \mod_competvet\external\get_plannings
 */
final class get_plannings_test extends \advanced_testcase {
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
     * Test get_plannings returns zero-based list payloads.
     */
    public function test_execute_returns_list_shaped_group_payloads(): void {
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $competvet = competvet::get_from_situation($situation);

        $result = $this->get_plannings(['cmid' => $competvet->get_course_module_id()]);

        $this->assertNotEmpty($result['groups']);
        $this->assertIsList($result['groups']);
        $this->assertNotEmpty($result['plannings']);
        $this->assertIsList($result['plannings']);

        foreach ($result['plannings'] as $planning) {
            $this->assertNotEmpty($planning['groups']);
            $this->assertIsList($planning['groups']);
        }
    }

    /**
     * Helper for get_plannings::execute.
     *
     * @param array $args
     * @return array
     */
    protected function get_plannings(array $args): array {
        $params = get_plannings::validate_parameters(get_plannings::execute_parameters(), $args);
        $returnvalue = get_plannings::execute(...array_values($params));
        return external_api::clean_returnvalue(get_plannings::execute_returns(), $returnvalue);
    }
}
