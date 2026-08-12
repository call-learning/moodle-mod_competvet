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
use advanced_testcase;
use mod_competvet\tests\test_data_definition;

/**
 * Setup Tests
 *
 * @package     mod_competvet
 * @copyright   2023 CALL Learning <contact@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversFunction('competvet_delete_instance')]
final class lib_test extends advanced_testcase {
    use test_data_definition;

    /**
     * Set up the test
     *
     * @return void
     */
    public function setUp(): void {
        global $CFG;
        parent::setUp();
        require_once($CFG->dirroot . '/mod/competvet/lib.php');
        $this->resetAfterTest();
    }

    /**
     * Test the deletion of a competvet instance
     *
     * @return void
     */
    public function test_delete(): void {
        global $DB;
        $this->prepare_scenario('set_2');
        $this->set_current_date();
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

    /**
     * Numeric CompetVet activities create a points grade item.
     *
     * @return void
     */
    public function test_numeric_grade_item_is_created_in_points_mode(): void {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $cm = $generator->create_module('competvet', ['course' => $course->id, 'grade' => 10]);

        $item = \grade_item::fetch([
            'itemtype' => 'mod',
            'itemmodule' => 'competvet',
            'iteminstance' => $cm->id,
            'courseid' => $course->id,
            'itemnumber' => 0,
        ]);

        $this->assertSame(GRADE_TYPE_VALUE, (int) $item->gradetype);
        $this->assertSame(10.0, (float) $item->grademax);
    }

    /**
     * A drifted numeric grade item is normalised before it is read or written.
     *
     * @return void
     */
    public function test_numeric_grade_item_is_normalised_for_reads_and_writes(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $cm = $generator->create_module('competvet', ['course' => $course->id, 'grade' => 10]);
        $item = \grade_item::fetch([
            'itemtype' => 'mod',
            'itemmodule' => 'competvet',
            'iteminstance' => $cm->id,
            'courseid' => $course->id,
            'itemnumber' => 0,
        ]);

        // Simulate an incompatible gradebook edit.
        $DB->update_record('grade_items', (object) [
            'id' => $item->id,
            'gradetype' => GRADE_TYPE_NONE,
            'scaleid' => null,
        ]);

        $competvet = competvet::get_from_cmid($cm->cmid);
        $this->assertSame(GRADE_TYPE_VALUE, (int) $competvet->get_grade_item()->gradetype);
        $this->assertSame(10, $competvet->get_grade_type_for(0));

        // The public grade update path applies the same points configuration.
        competvet_grade_item_update($cm);
        $item = \grade_item::fetch(['id' => $item->id]);
        $this->assertSame(GRADE_TYPE_VALUE, (int) $item->gradetype);
        $this->assertSame(10.0, (float) $item->grademax);
    }
}
