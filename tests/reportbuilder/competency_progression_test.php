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

namespace mod_competvet\reportbuilder;

use context_module;
use core_reportbuilder\system_report_factory;
use mod_competvet\competvet;
use mod_competvet\local\api\progression;
use mod_competvet\local\persistent\situation;
use mod_competvet\reportbuilder\local\helpers\progression_format;
use mod_competvet\reportbuilder\local\systemreports\competency_progression;
use mod_competvet\tests\test_data_definition;

/**
 * Competency progression report tests.
 *
 * @package   mod_competvet
 * @copyright 2026 CALL Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
/**
 * Tests for the competency progression report.
 *
 * @covers \mod_competvet\reportbuilder\local\systemreports\competency_progression
 */
final class competency_progression_test extends \advanced_testcase {
    use test_data_definition;

    public function test_report_is_buildable_and_has_unique_context_rows(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->prepare_scenario('set_3');
        $this->set_current_date();
        progression::refresh_all();

        $situation = situation::get_record(['shortname' => 'SIT1']);
        $competvet = competvet::get_from_situation($situation);
        $report = system_report_factory::create(
            competency_progression::class,
            context_module::instance($competvet->get_course_module_id()),
            '',
            '',
            0,
            ['downloadable' => true, 'hasfilters' => true]
        );

        $this->assertNotEmpty($report->get_columns());
        foreach (
            ['progression_total', 'progression_acquired', 'progression_evaluated_not_acquired',
                'progression_not_evaluated'] as $field
        ) {
            $this->assertTrue($report->get_columns()["progression:{$field}"]->get_is_sortable());
            $this->assertArrayHasKey("progression:{$field}", $report->get_filters());
        }
        $duplicates = $DB->get_records_sql(
            'SELECT studentid, situationid, planningid, criterionid, COUNT(*) AS rowcount
               FROM {competvet_progression}
              GROUP BY studentid, situationid, planningid, criterionid
             HAVING COUNT(*) > 1',
        );
        $this->assertEmpty($duplicates);
    }

    public function test_format_count_accepts_integer_values(): void {
        $this->assertSame('3', progression_format::format_count(3));
        $this->assertSame('0', progression_format::format_count(null));
    }
}
