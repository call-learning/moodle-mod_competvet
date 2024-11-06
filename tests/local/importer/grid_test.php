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

namespace mod_competvet\local\importer;

use advanced_testcase;
use mod_competvet\local\persistent\criterion;
use mod_competvet\local\persistent\grid;

/**
 * Evaluation Grid Test
 *
 * @package     mod_competvet
 * @copyright   2023 CALL Learning <contact@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class grid_test extends advanced_testcase {
    /**
     * Sample file path
     */
    const SAMPLE_FILE_PATH = '/mod/competvet/tests/fixtures/importer/sample_grid.csv';
    /**
     * Sample file path
     */
    const SAMPLE_MODIFIED_FILE_PATH = '/mod/competvet/tests/fixtures/importer/sample_grid_with_modif.csv';

    /**
     * Test import criterion.
     *
     * @return void
     * @covers \mod_competvet\local\persistent\grid
     */
    public function test_import_criterion(): void {
        global $CFG;
        $this->resetAfterTest();
        $evalgrid =
            new grid(
                0,
                (object) ['name' => 'Test grid', 'idnumber' => 'TESTGRID', 'type' => grid::COMPETVET_CRITERIA_EVALUATION]
            );
        $evalgrid->create();
        $criterionimporter = new criterion_importer(criterion::class);
        $criterionimporter->import($CFG->dirroot . self::SAMPLE_FILE_PATH);
        $this->assertEquals(40, criterion::count_records(['gridid' => intval($evalgrid->get('id'))]));
        foreach (['Q001', 'Q035'] as $critname) {
            $crit = criterion::get_record(['gridid' => $evalgrid->get('id'), 'idnumber' => $critname]);
            $this->assertEquals(
                5,
                criterion::count_records(['gridid' => $evalgrid->get('id'), 'parentid' => $crit->get('id')])
            );
            foreach (criterion::get_records(['gridid' => $evalgrid->get('id'), 'parentid' => $crit->get('id')]) as $c) {
                $this->assertNotEmpty($c->get('sort'));
            }
        }
    }

    /**
     * Test import criterion.
     *
     * @return void
     * @covers \mod_competvet\local\persistent\grid
     */
    public function test_import_criterion_update(): void {
        global $CFG;
        $this->resetAfterTest();
        $evalgrid = new grid(0, (object) [
            'name' => 'Test grid',
            'idnumber' => 'TESTGRID',
            'type' => grid::COMPETVET_CRITERIA_EVALUATION,
        ]
        );
        $evalgrid->create();
        $criterionimporter = new criterion_importer(criterion::class);
        $criterionimporter->import($CFG->dirroot . self::SAMPLE_FILE_PATH);
        $this->assertEquals(40, criterion::count_records(['gridid' => $evalgrid->get('id')]));
        $criterionimporter = new criterion_importer(criterion::class);
        $criterionimporter->import($CFG->dirroot . self::SAMPLE_FILE_PATH);
        $this->assertEquals(40, criterion::count_records(['gridid' => $evalgrid->get('id')]));
        $criterionimporter->import($CFG->dirroot . self::SAMPLE_MODIFIED_FILE_PATH);
        $this->assertEquals(40, criterion::count_records(['gridid' => $evalgrid->get('id')]));
        $crit01 = criterion::get_record(['gridid' => $evalgrid->get('id'), 'idnumber' => 'Q001']);
        $crit02 = criterion::get_record(['gridid' => $evalgrid->get('id'), 'idnumber' => 'Q002']);
        $this->assertEquals('Savoir être bien', $crit01->get('label'));
        $this->assertEquals('Respect des horaires de travail et des consignes de sécurité', $crit02->get('label'));
    }
}
