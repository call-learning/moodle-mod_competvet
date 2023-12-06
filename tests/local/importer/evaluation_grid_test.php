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
use context_course;
use context_module;
use context_system;
use mod_competvet\local\persistent\criterion;
use mod_competvet\local\persistent\evaluation_grid;
use mod_competvet\task\post_install;

/**
 * Evaluation Grid Test
 *
 * @package     mod_competvet
 * @copyright   2023 CALL Learning <contact@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class evaluation_grid_test extends advanced_testcase {
    /**
     * Sample file path
     */
    const SAMPLE_FILE_PATH = '/mod/competvet/tests/fixtures/importer/sample_evaluation_grid.csv';
    /**
     * Sample file path
     */
    const SAMPLE_MODIFIED_FILE_PATH = '/mod/competvet/tests/fixtures/importer/sample_evaluation_with_modif.csv';
    /**
     * Test import criterion.
     * @return void
     * @covers \mod_competvet\local\persistent\evaluation_grid
     */
    public function test_import_criterion() {
        global $CFG;
        $this->resetAfterTest();
        $evalgrid = new evaluation_grid(0, (object) ['name' => 'Test grid', 'idnumber' => 'TESTGRID']);
        $evalgrid->create();
        $criterionimporter = new criterion_importer(criterion::class);
        $criterionimporter->import($CFG->dirroot . self::SAMPLE_FILE_PATH);
        $this->assertEquals(40, criterion::count_records(['evalgridid' => $evalgrid->get('id')]));
        foreach (['Q001', 'Q035'] as $critname) {
            $crit = criterion::get_record(['evalgridid' => $evalgrid->get('id'), 'idnumber' => $critname]);
            $this->assertEquals(
                5,
                criterion::count_records(['evalgridid' => $evalgrid->get('id'), 'parentid' => $crit->get('id')])
            );
            $this->assertSame([1, 2, 3, 4, 5], array_map(
                fn($c) => $c->get('sort'),
                criterion::get_records(['evalgridid' => $evalgrid->get('id'), 'parentid' => $crit->get('id')])
            ));
        }
    }
    /**
     * Test import criterion.
     * @return void
     * @covers \mod_competvet\local\persistent\evaluation_grid
     */
    public function test_import_criterion_update() {
        global $CFG;
        $this->resetAfterTest();
        $evalgrid = new evaluation_grid(0, (object) ['name' => 'Test grid', 'idnumber' => 'TESTGRID']);
        $evalgrid->create();
        $criterionimporter = new criterion_importer(criterion::class);
        $criterionimporter->import($CFG->dirroot . self::SAMPLE_FILE_PATH);
        $this->assertEquals(40, criterion::count_records(['evalgridid' => $evalgrid->get('id')]));
        $criterionimporter = new criterion_importer(criterion::class);
        $criterionimporter->import($CFG->dirroot . self::SAMPLE_FILE_PATH);
        $this->assertEquals(40, criterion::count_records(['evalgridid' => $evalgrid->get('id')]));
        $criterionimporter->import($CFG->dirroot . self::SAMPLE_MODIFIED_FILE_PATH);
        $this->assertEquals(40, criterion::count_records(['evalgridid' => $evalgrid->get('id')]));
        $crit01 = criterion::get_record(['evalgridid' => $evalgrid->get('id'), 'idnumber' => 'Q001']);
        $crit02 = criterion::get_record(['evalgridid' => $evalgrid->get('id'), 'idnumber' => 'Q002']);
        $this->assertEquals('Savoir être bien', $crit01->get('label'));
        $this->assertEquals('Respect des horaires de travail et des consignes de sécurité', $crit02->get('label'));
    }
}
