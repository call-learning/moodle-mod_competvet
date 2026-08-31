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

namespace mod_competvet\local\api;

use advanced_testcase;
use mod_competvet\local\persistent\criterion;
use mod_competvet\local\persistent\grid;
use mod_competvet\local\persistent\observation;
use mod_competvet\local\persistent\observation_criterion_level;
use mod_competvet\local\persistent\situation;
use mod_competvet\utils;

/**
 * Criteria API test
 *
 * @package     mod_competvet
 * @copyright   2026 CALL Learning <contact@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers      \mod_competvet\local\api\criteria
 */
final class criteria_test extends advanced_testcase {
    /**
     * Set up the test.
     *
     * @return void
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->create_default_grids();
    }

    /**
     * Test that duplicate_grid copies the criteria of a situation grid, keeps their structure
     * and does not modify the source grid.
     *
     * @return void
     */
    public function test_duplicate_grid_copies_criteria_and_preserves_structure(): void {
        $course = $this->getDataGenerator()->create_course();
        $competvet = $this->getDataGenerator()->get_plugin_generator('mod_competvet')
            ->create_instance(['course' => $course->id]);
        $situation = situation::get_record(['competvetid' => $competvet->id]);

        // Create a grid bound to the situation with two parents, two options and one orphan criterion.
        [$grid, $oldcriteria] = $this->create_grid_with_criteria($situation->get('id'), 'SRCGRID', 'Source grid');

        $newgridid = criteria::duplicate_grid($grid->get('id'));

        $this->assertNotEquals($grid->get('id'), $newgridid);
        $newgrid = grid::get_record(['id' => $newgridid]);
        $this->assertNotNull($newgrid);
        $this->assertEquals($grid->get('name') . get_string('copysuffix', 'mod_competvet'), $newgrid->get('name'));
        $this->assertNotEquals($grid->get('idnumber'), $newgrid->get('idnumber'));
        // The new grid idnumber must be unique.
        $this->assertEquals(1, grid::count_records(['idnumber' => $newgrid->get('idnumber')]));
        // Scope, type and sortorder are preserved.
        $this->assertEquals($situation->get('id'), $newgrid->get('situationid'));
        $this->assertEquals($grid->get('type'), $newgrid->get('type'));
        $this->assertEquals($grid->get('sortorder'), $newgrid->get('sortorder'));

        // The new criteria keep the label, idnumber, sort and grade of their source.
        $newcriteria = [];
        foreach (criterion::get_records(['gridid' => $newgridid]) as $criterionrecord) {
            $newcriteria[$criterionrecord->get('idnumber')] = $criterionrecord;
        }
        $this->assertEqualsCanonicalizing(array_keys($oldcriteria), array_keys($newcriteria));
        foreach ($oldcriteria as $idnumber => $oldcriterion) {
            $newcriterion = $newcriteria[$idnumber];
            $this->assertEquals($oldcriterion->get('label'), $newcriterion->get('label'));
            $this->assertEquals($oldcriterion->get('sort'), $newcriterion->get('sort'));
            $this->assertEquals($oldcriterion->get('grade'), $newcriterion->get('grade'));
        }

        // Parent ids are remapped to the new parent criteria.
        $this->assertEquals(0, $newcriteria['P1']->get('parentid'));
        $this->assertEquals($newcriteria['P1']->get('id'), $newcriteria['O1']->get('parentid'));
        $this->assertEquals($newcriteria['P2']->get('id'), $newcriteria['O2']->get('parentid'));
        // The orphan criterion does not have a known parent in the copied grid.
        $this->assertEquals(0, $newcriteria['ORPHAN']->get('parentid'));

        // The source grid and its criteria are untouched.
        $grid = grid::get_record(['id' => $grid->get('id')]);
        $this->assertEquals('Source grid', $grid->get('name'));
        $this->assertEquals(5, criterion::count_records(['gridid' => $grid->get('id')]));
        $sourceoption = criterion::get_record(['id' => $oldcriteria['O1']->get('id')]);
        $this->assertEquals($oldcriteria['P1']->get('id'), $sourceoption->get('parentid'));
        $sourceoption2 = criterion::get_record(['id' => $oldcriteria['O2']->get('id')]);
        $this->assertEquals($oldcriteria['P2']->get('id'), $sourceoption2->get('parentid'));

        // The copied criteria are new records.
        $oldids = array_map(static fn (criterion $c) => $c->get('id'), $oldcriteria);
        $newids = array_map(static fn (criterion $c) => $c->get('id'), $newcriteria);
        $this->assertSame([], array_intersect($oldids, $newids));
    }

    /**
     * Test that duplicate_grid can duplicate the default grid.
     *
     * @return void
     */
    public function test_duplicate_grid_of_default_grid(): void {
        $defaultgrid = grid::get_record(['idnumber' => 'DEFAULTEVALGRID']);
        $this->assertNotNull($defaultgrid);
        // The default grid cannot be deleted...
        $this->assertFalse($defaultgrid->can_delete());
        // ... but it can be duplicated.
        $newgridid = criteria::duplicate_grid($defaultgrid->get('id'));

        $newgrid = grid::get_record(['id' => $newgridid]);
        $this->assertNotNull($newgrid);
        $this->assertNotEquals($defaultgrid->get('id'), $newgridid);
        $this->assertEquals($defaultgrid->get('name') . get_string('copysuffix', 'mod_competvet'), $newgrid->get('name'));
        $this->assertNotEquals('DEFAULTEVALGRID', $newgrid->get('idnumber'));
        $this->assertEquals(0, $newgrid->get('situationid'));
        $this->assertEquals($defaultgrid->get('type'), $newgrid->get('type'));
        // The default grid criteria are all copied (whatever number they are).
        $sourcecriteria = criterion::count_records(['gridid' => $defaultgrid->get('id')]);
        $this->assertEquals($sourcecriteria, criterion::count_records(['gridid' => $newgridid]));
        // The source default grid is untouched.
        $this->assertEquals($sourcecriteria, criterion::count_records(['gridid' => $defaultgrid->get('id')]));
    }

    /**
     * Test that duplicate_grid can duplicate a grid that is in use,
     * that is to say referenced by a situation and having observation results.
     *
     * @return void
     */
    public function test_duplicate_grid_of_used_grid(): void {
        $course = $this->getDataGenerator()->create_course();

        $grid = $this->create_test_grid(0, 'USEDGRID', 'Used grid');
        $competvet = $this->getDataGenerator()->get_plugin_generator('mod_competvet')
            ->create_instance(['course' => $course->id, 'evalgrid' => $grid->get('id')]);
        $situation = situation::get_record(['competvetid' => $competvet->id]);
        $this->assertEquals($grid->get('id'), $situation->get('evalgrid'));

        $criterionrecord = new criterion(0, (object) [
            'gridid' => $grid->get('id'),
            'label' => 'Criterion 1',
            'idnumber' => 'CRIT1',
            'parentid' => 0,
            'sort' => 1,
        ]);
        $criterionrecord->create();

        $observationrecord = new observation(0, (object) [
            'situationid' => 0,
            'observerid' => 0,
            'observedid' => 0,
            'planningid' => 0,
            'studentid' => 0,
            'timeobserved' => time(),
        ]);
        $observationrecord->create();

        $observationcriterionlevel = new observation_criterion_level(0, (object) [
            'criterionid' => $criterionrecord->get('id'),
            'level' => 2,
            'observationid' => $observationrecord->get('id'),
        ]);
        $observationcriterionlevel->create();

        $this->assertTrue(utils::is_grid_used($grid));

        $newgridid = criteria::duplicate_grid($grid->get('id'));
        $newgrid = grid::get_record(['id' => $newgridid]);
        $this->assertNotNull($newgrid);
        $this->assertNotEquals($grid->get('id'), $newgridid);
        $this->assertEquals(1, criterion::count_records(['gridid' => $newgridid]));
        // The source grid is still in use, the copy is not.
        $this->assertTrue(utils::is_grid_used($grid));
        $this->assertFalse(utils::is_grid_used($newgrid));
    }

    /**
     * Test that duplicate_grid throws an exception for an unknown grid id.
     *
     * @return void
     */
    public function test_duplicate_grid_missing_grid_throws_invaliddata(): void {
        $this->expectException('moodle_exception');
        $this->expectExceptionMessage('Invalid data for grid');

        criteria::duplicate_grid(999999);
    }

    /**
     * Test that duplicate_grid is not allowed for a user without the global grid capability.
     *
     * @return void
     */
    public function test_duplicate_grid_noaccess_without_global_capability(): void {
        $grid = $this->create_test_grid(0, 'GLOBALGRID', 'Global grid');
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user->id);

        $this->expectException('moodle_exception');
        $this->expectExceptionMessage("You don't have access to this page");

        criteria::duplicate_grid($grid->get('id'));
    }

    /**
     * Test that duplicate_grid is not allowed for a user without the activity grid capability.
     *
     * @return void
     */
    public function test_duplicate_grid_noaccess_without_activity_capability(): void {
        $course = $this->getDataGenerator()->create_course();
        $competvet = $this->getDataGenerator()->get_plugin_generator('mod_competvet')
            ->create_instance(['course' => $course->id]);
        $situation = situation::get_record(['competvetid' => $competvet->id]);
        $grid = $this->create_test_grid($situation->get('id'), 'SITGRID', 'Situation grid');
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user->id);

        $this->expectException('moodle_exception');
        $this->expectExceptionMessage("You don't have access to this page");

        criteria::duplicate_grid($grid->get('id'));
    }

    /**
     * Create a grid with the given scope.
     *
     * @param int $situationid - The situation id the grid is bound to (0 for a global grid)
     * @param string $idnumber - The grid idnumber
     * @param string $name - The grid name
     * @return grid - The created grid
     */
    private function create_test_grid(int $situationid, string $idnumber, string $name): grid {
        $grid = new grid(0, (object) [
            'name' => $name,
            'idnumber' => $idnumber,
            'type' => grid::COMPETVET_CRITERIA_EVALUATION,
            'situationid' => $situationid,
            'sortorder' => 5,
        ]);
        $grid->create();
        return $grid;
    }

    /**
     * Create a grid with two parent criteria, two options and one orphan criterion.
     *
     * @param int $situationid - The situation id the grid is bound to
     * @param string $idnumber - The grid idnumber
     * @param string $name - The grid name
     * @return array - The created grid and the created criteria indexed by idnumber
     */
    private function create_grid_with_criteria(int $situationid, string $idnumber, string $name): array {
        $grid = $this->create_test_grid($situationid, $idnumber, $name);

        // Criterion idnumber, label, parent idnumber (null for a parent), sort, grade.
        $definitions = [
            ['P1', 'Parent 1', null, 1, 10.0],
            ['O1', 'Option 1', 'P1', 1, 2.0],
            ['P2', 'Parent 2', null, 2, 5.0],
            ['O2', 'Option 2', 'P2', 1, 3.0],
            ['ORPHAN', 'Orphan criterion', 999999, 3, 4.0],
        ];
        $criteria = [];
        foreach ($definitions as [$id, $label, $parentidnumber, $sort, $grade]) {
            $parentid = 0;
            if (is_string($parentidnumber)) {
                $parentid = $criteria[$parentidnumber]->get('id');
            }
            $criterionrecord = new criterion(0, (object) [
                'gridid' => $grid->get('id'),
                'label' => $label,
                'idnumber' => $id,
                'parentid' => $parentid,
                'sort' => $sort,
                'grade' => $grade,
            ]);
            $criterionrecord->create();
            $criteria[$id] = $criterionrecord;
        }

        return [$grid, $criteria];
    }

    /**
     * Create the default grids required by the module.
     * These are normally created during installation but may be missing in the test database,
     * so they are created only if they do not already exist.
     *
     * @return void
     */
    private function create_default_grids(): void {
        global $DB;
        // Check if evaluation grid already exists.
        if (!$DB->get_record('competvet_grid', ['idnumber' => 'DEFAULTEVALGRID'])) {
            $evalgrid = new grid(0, (object) [
                'name' => 'Default evaluation grid',
                'idnumber' => 'DEFAULTEVALGRID',
                'type' => grid::COMPETVET_CRITERIA_EVALUATION,
                'sortorder' => 0,
            ]);
            $evalgrid->create();
            // Create default criterion for evaluation grid.
            $evalcriterion = new criterion(0, (object) [
                'label' => 'EVAL1',
                'idnumber' => 'EVAL1',
                'gridid' => $evalgrid->get('id'),
                'sort' => 0,
            ]);
            $evalcriterion->create();
        }
        // Check if certification grid already exists.
        if (!$DB->get_record('competvet_grid', ['idnumber' => 'DEFAULTCERTIFGRID'])) {
            $certifgrid = new grid(0, (object) [
                'name' => 'Default certification grid',
                'idnumber' => 'DEFAULTCERTIFGRID',
                'type' => grid::COMPETVET_CRITERIA_CERTIFICATION,
                'sortorder' => 0,
            ]);
            $certifgrid->create();
        }
        // Check if list grid already exists.
        if (!$DB->get_record('competvet_grid', ['idnumber' => 'DEFAULTLISTGRID'])) {
            $listgrid = new grid(0, (object) [
                'name' => 'Default list grid',
                'idnumber' => 'DEFAULTLISTGRID',
                'type' => grid::COMPETVET_CRITERIA_LIST,
                'sortorder' => 0,
            ]);
            $listgrid->create();
        }
    }
}
