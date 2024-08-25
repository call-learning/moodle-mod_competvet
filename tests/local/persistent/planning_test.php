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
namespace mod_competvet\local\persistent;
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/mod/competvet/tests/test_data_definition.php');

use advanced_testcase;
use test_data_definition;

/**
 * Planning API persistent test
 *
 * @package     mod_competvet
 * @copyright   2023 CALL Learning <contact@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class planning_test extends advanced_testcase {
    use test_data_definition;

    /**
     * Setup the test
     *
     * @return void
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->prepare_scenario('set_3');
    }

    /**
     * Get all for user
     *
     * @param string $username
     * @param array $expected
     * @return void
     * @covers       \mod_competvet\local\api\situations::get_all_situations_for
     */
    public function test_delete_planning_and_related() {
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $plannings = planning::get_records(['situationid' => $situation->get('id')]);
        $this->assertGreaterThan(0, observation::count_records());
        $this->assertGreaterThan(0, observation_comment::count_records());
        $this->assertGreaterThan(0, observation_criterion_level::count_records());
        $this->assertGreaterThan(0, cert_decl::count_records());
        $this->assertGreaterThan(0, cert_decl_asso::count_records());
        $this->assertGreaterThan(0, cert_valid::count_records());
        $this->assertGreaterThan(0, case_entry::count_records());
        foreach ($plannings as $planning) {
            $planning->delete();
        }

        $this->assertEquals(0, observation::count_records());
        $this->assertEquals(0, observation_comment::count_records());
        $this->assertEquals(0, observation_criterion_level::count_records());
        $this->assertEquals(0, cert_decl::count_records());
        $this->assertEquals(0, cert_decl_asso::count_records());
        $this->assertEquals(0, cert_valid::count_records());
        $this->assertEquals(0, case_entry::count_records());

    }
}
