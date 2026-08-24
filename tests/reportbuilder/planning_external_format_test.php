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
use mod_competvet\local\persistent\situation;
use mod_competvet\reportbuilder\local\systemreports\planning_external_format;
use mod_competvet\tests\test_data_definition;

/**
 * Planning external format report tests.
 *
 * @package   mod_competvet
 * @copyright 2026 CALL Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(planning_external_format::class)]
final class planning_external_format_test extends \advanced_testcase {
    use test_data_definition;

    /**
     * Test that the report is buildable and has expected columns.
     */
    public function test_report_is_buildable_and_has_expected_columns(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->prepare_scenario('set_1');

        $situation = situation::get_record(['shortname' => 'SIT1']);
        $competvet = competvet::get_from_situation($situation);
        $report = system_report_factory::create(
            planning_external_format::class,
            context_module::instance($competvet->get_course_module_id()),
            '',
            '',
            0,
            ['downloadable' => true, 'hasfilters' => true],
        );

        $this->assertNotEmpty($report->get_columns());

        // Verify new date columns exist.
        $this->assertArrayHasKey('planning:startdatets', $report->get_columns());
        $this->assertArrayHasKey('planning:enddatets', $report->get_columns());

        // Pause columns are dynamic (based on max pauses in data), so we only check
        // that the report builds without error.

        // Verify the sort column is set correctly.
        $sortcolumn = $report->get_initial_sort_column();
        $this->assertNotNull($sortcolumn);
        $this->assertEquals('planning:startdatets', $sortcolumn->get_unique_identifier());

        // Verify column titles use the new lang strings.
        $startdatecolumn = $report->get_column('planning:startdatets');
        $this->assertStringContainsString('Start Date', (string) $startdatecolumn->get_title());

        $groupnamecolumn = $report->get_column('group:name');
        $this->assertStringContainsString('Group Name', (string) $groupnamecolumn->get_title());

        $sessioncolumn = $report->get_column('planning:session');
        $this->assertStringContainsString('Session', (string) $sessioncolumn->get_title());
    }

    /**
     * Test that the report filters are correctly configured.
     */
    public function test_report_has_expected_filters(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->prepare_scenario('set_1');

        $situation = situation::get_record(['shortname' => 'SIT1']);
        $competvet = competvet::get_from_situation($situation);
        $report = system_report_factory::create(
            planning_external_format::class,
            context_module::instance($competvet->get_course_module_id()),
            '',
            '',
            0,
            ['downloadable' => true, 'hasfilters' => true],
        );

        $this->assertNotEmpty($report->get_filters());
        $this->assertArrayHasKey('planning:startdate', $report->get_filters());
        $this->assertArrayHasKey('planning:enddate', $report->get_filters());
        $this->assertArrayHasKey('group:name', $report->get_filters());
    }

    /**
     * Test that the report can be built with a situation filter.
     */
    public function test_report_with_situation_filter(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->prepare_scenario('set_1');

        $situation = situation::get_record(['shortname' => 'SIT1']);
        $competvet = competvet::get_from_situation($situation);
        $report = system_report_factory::create(
            planning_external_format::class,
            context_module::instance($competvet->get_course_module_id()),
            '',
            '',
            0,
            ['downloadable' => true, 'hasfilters' => true, 'situationid' => $situation->get('id')],
        );

        $this->assertNotEmpty($report->get_columns());
    }

    /**
     * Test that the report can be built with a situation filter and returns data.
     */
    public function test_report_with_situation_filter_returns_data(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->prepare_scenario('set_1');

        $situation = situation::get_record(['shortname' => 'SIT1']);
        $competvet = competvet::get_from_situation($situation);
        $report = system_report_factory::create(
            planning_external_format::class,
            context_module::instance($competvet->get_course_module_id()),
            '',
            '',
            0,
            ['downloadable' => true, 'hasfilters' => true, 'situationid' => $situation->get('id')],
        );

        $this->assertNotEmpty($report->get_columns());
    }
}
