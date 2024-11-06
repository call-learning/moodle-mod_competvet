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

namespace mod_competvet;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/mod/competvet/tests/test_data_definition.php');
require_once($CFG->dirroot . '/mod/competvet/lib.php');

use advanced_testcase;
use test_data_definition;

/**
 * Setup Tests
 *
 * @package     mod_competvet
 * @copyright   2023 CALL Learning <contact@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class lib_test extends advanced_testcase {
    use test_data_definition;

    /**
     * Set up the test
     *
     * @return void
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Test the deletion of a competvet instance
     *
     * @return void
     * @covers ::competvet_delete_instance
     */
    public function test_delete(): void {
        global $DB;
        $this->prepare_scenario('set_2');
        // Test deletion.

        $competvets = $DB->get_records('competvet');
        foreach ($competvets as $competvet) {
            competvet_delete_instance($competvet->id);
        }
        $this->assertEquals(0, $DB->count_records('competvet'));
        $this->assertEquals(0, $DB->count_records('competvet_situation'));
        $this->assertEquals(0, $DB->count_records('competvet_planning'));
        $this->assertEquals(0, $DB->count_records('competvet_observation'));
        $this->assertEquals(0, $DB->count_records('competvet_obs_comment'));
        $this->assertEquals(0, $DB->count_records('competvet_obs_crit_level'));
        $this->assertEquals(0, $DB->count_records('competvet_obs_crit_com'));

        $this->assertEquals(0, $DB->count_records('competvet_grades'));
        $this->assertEquals(0, $DB->count_records('competvet_todo'));

        $this->assertEquals(0, $DB->count_records('competvet_cert_decl'));
        $this->assertEquals(0, $DB->count_records('competvet_cert_decl_asso'));
        $this->assertEquals(0, $DB->count_records('competvet_cert_valid'));

        $this->assertEquals(0, $DB->count_records('competvet_case_entry'));
        $this->assertEquals(0, $DB->count_records('competvet_case_data'));
    }
}
