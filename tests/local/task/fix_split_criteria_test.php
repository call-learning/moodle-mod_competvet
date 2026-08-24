<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Tests for the fix_split_criteria adhoc task.
 *
 * @package    mod_competvet
 * @copyright  2026 CALL Learning <contact@call-learning.fr>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_competvet\local\task;

use mod_competvet\local\persistent\criterion;
use mod_competvet\local\persistent\grid;
use mod_competvet\local\persistent\observation_criterion_level;
use mod_competvet\local\persistent\observation_criterion_comment;
use mod_competvet\local\persistent\cert_decl;
use mod_competvet\local\persistent\progression_result;
use mod_competvet\task\fix_split_criteria;

/**
 * Tests for the fix_split_criteria adhoc task.
 *
 * @package    mod_competvet
 * @copyright  2026 CALL Learning <contact@call-learning.fr>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
/**
 * Tests for split criteria repair.
 *
 * @covers \mod_competvet\task\fix_split_criteria
 */
final class fix_split_criteria_test extends \advanced_testcase {
    /**
     * Original Q038 split label (before merge).
     */
    const Q038_SPLIT_LABEL = 'Capacité à présenter à l\'oral de façon claire, concise et précise des informations';

    /**
     * Original Q039 split label (before merge).
     */
    const Q039_SPLIT_LABEL = 'médicales avec un vocabulaire scientifique approprié';

    /**
     * Original Q040 label (to be renamed to Q039).
     */
    const Q040_LABEL = 'Capacité à expliquer avec les termes appropriés et en adaptant son discours'
        . ' en fonction du public (clients, personnel, enseignants, autres étudiants, …)';

    /**
     * Original Q025 split label (before merge).
     */
    const Q025_SPLIT_LABEL = 'Capacité à faire les gestes techniques correspondant à son niveau, en respectant les';

    /**
     * Original Q026 split label (before merge).
     */
    const Q026_SPLIT_LABEL = 'bonnes pratiques et avec une habileté technique suffisante';

    /**
     * Set up test data.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Test that the task merges Q038 and Q039 into a single Q038 criterion.
     */
    public function test_merge_q038_q039(): void {
        // Create a grid.
        $grid = new grid(0, (object) [
            'name' => 'Test grid',
            'idnumber' => 'TESTGRID',
            'type' => grid::COMPETVET_CRITERIA_EVALUATION,
        ]);
        $grid->create();
        $gridid = $grid->get('id');

        // Create Q038 criterion with original split label.
        $q038 = new criterion(0, (object) [
            'label' => self::Q038_SPLIT_LABEL,
            'idnumber' => 'Q038',
            'parentid' => 0,
            'gridid' => $gridid,
            'sort' => 1,
        ]);
        $q038->create();
        $q038id = $q038->get('id');

        // Create Q039 criterion with original split label.
        $q039 = new criterion(0, (object) [
            'label' => self::Q039_SPLIT_LABEL,
            'idnumber' => 'Q039',
            'parentid' => 0,
            'gridid' => $gridid,
            'sort' => 2,
        ]);
        $q039->create();
        $q039id = $q039->get('id');

        // Create target records too, exercising the collision handling during the merge.
        $targetobslevel = new observation_criterion_level(0, (object) [
            'criterionid' => $q038id,
            'observationid' => 1,
            'level' => 2,
            'isactive' => true,
        ]);
        $targetobslevel->create();

        $targetprogression = new progression_result(0, (object) [
            'studentid' => 1,
            'situationid' => 1,
            'planningid' => 1,
            'criterionid' => $q038id,
            'status' => 1,
            'bestlevel' => 2,
            'totalobservations' => 1,
            'timecalculated' => 1,
        ]);
        $targetprogression->create();

        // Create Q040 criterion with production label (to be renamed to Q039).
        $q040 = new criterion(0, (object) [
            'label' => self::Q040_LABEL,
            'idnumber' => 'Q040',
            'parentid' => 0,
            'gridid' => $gridid,
            'sort' => 3,
        ]);
        $q040->create();
        $q040id = $q040->get('id');

        // Create some observation criterion levels referencing Q039.
        $obslevel1 = new observation_criterion_level(0, (object) [
            'criterionid' => $q039id,
            'observationid' => 1,
            'level' => 3,
            'isactive' => true,
        ]);
        $obslevel1->create();

        $oldprogression = new progression_result(0, (object) [
            'studentid' => 1,
            'situationid' => 1,
            'planningid' => 1,
            'criterionid' => $q039id,
            'status' => 1,
            'bestlevel' => 3,
            'totalobservations' => 1,
            'timecalculated' => 1,
        ]);
        $oldprogression->create();

        // Create some observation criterion comments referencing Q039.
        $obscritcom = new observation_criterion_comment(0, (object) [
            'criterionid' => $q039id,
            'observationid' => 1,
            'comment' => 'Test comment for Q039',
        ]);
        $obscritcom->create();

        // Create a cert_decl referencing Q039.
        $certdecl = new cert_decl(0, (object) [
            'criterionid' => $q039id,
            'planningid' => 1,
            'studentid' => 1,
            'level' => 3,
            'status' => 1,
            'comment' => 'Test comment',
            'commentformat' => 1,
        ]);
        $certdecl->create();

        // Run the task.
        $task = new fix_split_criteria();
        $task->execute();

        // Verify Q038 has the merged label.
        $mergedq038 = criterion::get_record(['id' => $q038id]);
        $this->assertNotNull($mergedq038);
        $expectedlabel = self::Q038_SPLIT_LABEL . ' ' . self::Q039_SPLIT_LABEL;
        $this->assertEquals($expectedlabel, $mergedq038->get('label'));

        // Verify the original Q039 (with split label) no longer exists.
        // Note: A new Q039 exists (the renamed Q040), so we check by label.
        $remainingq039 = criterion::get_records_select(
            "idnumber = :idnumber AND gridid = :gridid AND " . $this->get_sql_compare_text('label') . " = :label",
            ['idnumber' => 'Q039', 'gridid' => $gridid, 'label' => self::Q039_SPLIT_LABEL]
        );
        $this->assertEmpty($remainingq039);

        // Verify Q040 has been renamed to Q039.
        $renamedq039 = criterion::get_record(['idnumber' => 'Q039', 'gridid' => $gridid]);
        $this->assertNotNull($renamedq039);
        $this->assertEquals($q040id, $renamedq039->get('id'));
        $this->assertEquals(self::Q040_LABEL, $renamedq039->get('label'));

        // Verify all references have been updated from old Q039 to merged Q038.
        $this->assertFalse(observation_criterion_level::get_record(['id' => $obslevel1->get('id')]));
        $this->assertNotNull(observation_criterion_level::get_record(['id' => $targetobslevel->get('id')]));

        $updatedobscritcom = observation_criterion_comment::get_record(['id' => $obscritcom->get('id')]);
        $this->assertNotNull($updatedobscritcom);
        $this->assertEquals($q038id, $updatedobscritcom->get('criterionid'));

        $updatedcertdecl = cert_decl::get_record(['id' => $certdecl->get('id')]);
        $this->assertNotNull($updatedcertdecl);
        $this->assertEquals($q038id, $updatedcertdecl->get('criterionid'));

        $this->assertFalse(progression_result::get_record(['id' => $oldprogression->get('id')]));
        $this->assertNotNull(progression_result::get_record(['id' => $targetprogression->get('id')]));

        // Verify total criterion count: we started with 3 (Q038, Q039, Q040),
        // after merge: Q038+Q039→Q038 (1), Q040→Q039 (1) = 2 criteria.
        $totalcriteria = criterion::count_records(['gridid' => $gridid]);
        $this->assertEquals(2, $totalcriteria);
    }

    /**
     * Test that the task handles grids with only Q038 (no Q039 split).
     * Uses an arbitrary label that does not match the production label,
     * so the task should not modify it.
     */
    public function test_task_with_only_q038_no_q039(): void {
        // Create a grid with only Q038.
        $grid = new grid(0, (object) [
            'name' => 'Test grid 2',
            'idnumber' => 'TESTGRID2',
            'type' => grid::COMPETVET_CRITERIA_EVALUATION,
        ]);
        $grid->create();
        $gridid = $grid->get('id');

        // Create Q038 criterion with arbitrary label (not matching production).
        $q038 = new criterion(0, (object) [
            'label' => 'Some criterion Q038',
            'idnumber' => 'Q038',
            'parentid' => 0,
            'gridid' => $gridid,
            'sort' => 1,
        ]);
        $q038->create();

        // Run the task.
        $task = new fix_split_criteria();
        $task->execute();

        // Verify Q038 is unchanged (task only matches production labels).
        $unchangedq038 = criterion::get_record(['id' => $q038->get('id')]);
        $this->assertNotNull($unchangedq038);
        $this->assertEquals('Some criterion Q038', $unchangedq038->get('label'));

        // Verify no Q039 or Q040 was created in this grid.
        $this->assertEquals(1, criterion::count_records(['gridid' => $gridid]));
    }

    /**
     * Test that the task handles grids with only Q040 (no Q038/Q039 split).
     * Uses the production Q040 label so the task renames it to Q039.
     */
    public function test_task_with_only_q040(): void {
        // Create a grid with only Q040.
        $grid = new grid(0, (object) [
            'name' => 'Test grid 3',
            'idnumber' => 'TESTGRID3',
            'type' => grid::COMPETVET_CRITERIA_EVALUATION,
        ]);
        $grid->create();
        $gridid = $grid->get('id');

        // Create Q040 criterion with production label.
        $q040 = new criterion(0, (object) [
            'label' => self::Q040_LABEL,
            'idnumber' => 'Q040',
            'parentid' => 0,
            'gridid' => $gridid,
            'sort' => 1,
        ]);
        $q040->create();

        // Run the task.
        $task = new fix_split_criteria();
        $task->execute();

        // Verify Q040 has been renamed to Q039.
        $renamed = criterion::get_record(['idnumber' => 'Q039', 'gridid' => $gridid]);
        $this->assertNotNull($renamed);
        $this->assertEquals($q040->get('id'), $renamed->get('id'));
        $this->assertEquals(self::Q040_LABEL, $renamed->get('label'));

        // Verify total count.
        $this->assertEquals(1, criterion::count_records(['gridid' => $gridid]));
    }

    /**
     * Test that the task is idempotent (running twice produces the same result).
     * Uses production labels so the merge actually happens.
     */
    public function test_task_is_idempotent(): void {
        // Create a grid with Q038 and Q039 using production split labels.
        $grid = new grid(0, (object) [
            'name' => 'Test grid 4',
            'idnumber' => 'TESTGRID4',
            'type' => grid::COMPETVET_CRITERIA_EVALUATION,
        ]);
        $grid->create();
        $gridid = $grid->get('id');

        $q038 = new criterion(0, (object) [
            'label' => self::Q038_SPLIT_LABEL,
            'idnumber' => 'Q038',
            'parentid' => 0,
            'gridid' => $gridid,
            'sort' => 1,
        ]);
        $q038->create();

        $q039 = new criterion(0, (object) [
            'label' => self::Q039_SPLIT_LABEL,
            'idnumber' => 'Q039',
            'parentid' => 0,
            'gridid' => $gridid,
            'sort' => 2,
        ]);
        $q039->create();

        // Run the task first time.
        $task = new fix_split_criteria();
        $task->execute();

        // Get the merged criterion id.
        $mergedq038 = criterion::get_record(['idnumber' => 'Q038', 'gridid' => $gridid]);
        $this->assertNotNull($mergedq038);
        $firstlabel = $mergedq038->get('label');

        // Run the task a second time.
        $task->execute();

        // Verify the result is the same (idempotent).
        $mergedq038after = criterion::get_record(['id' => $mergedq038->get('id')]);
        $this->assertNotNull($mergedq038after);
        $this->assertEquals($firstlabel, $mergedq038after->get('label'));

        // Verify the label was NOT double-merged.
        $this->assertStringNotContainsString('médicales médicales', $mergedq038after->get('label'));

        // Verify no duplicate Q039 was created.
        $q039count = criterion::count_records(['idnumber' => 'Q039', 'gridid' => $gridid]);
        $this->assertEquals(0, $q039count);
    }

    /**
     * Test that the task merges Q025 and Q026 into a single Q025 criterion.
     * Uses production split labels so the merge actually happens.
     */
    public function test_merge_q025_q026(): void {
        // Create a grid.
        $grid = new grid(0, (object) [
            'name' => 'Test grid 5',
            'idnumber' => 'TESTGRID5',
            'type' => grid::COMPETVET_CRITERIA_EVALUATION,
        ]);
        $grid->create();
        $gridid = $grid->get('id');

        // Create Q025 criterion with original split label.
        $q025 = new criterion(0, (object) [
            'label' => self::Q025_SPLIT_LABEL,
            'idnumber' => 'Q025',
            'parentid' => 0,
            'gridid' => $gridid,
            'sort' => 1,
        ]);
        $q025->create();
        $q025id = $q025->get('id');

        // Create Q026 criterion with original split label.
        $q026 = new criterion(0, (object) [
            'label' => self::Q026_SPLIT_LABEL,
            'idnumber' => 'Q026',
            'parentid' => 0,
            'gridid' => $gridid,
            'sort' => 2,
        ]);
        $q026->create();
        $q026id = $q026->get('id');

        // Create some observation criterion levels referencing Q026.
        $obslevel1 = new observation_criterion_level(0, (object) [
            'criterionid' => $q026id,
            'observationid' => 1,
            'level' => 3,
            'isactive' => true,
        ]);
        $obslevel1->create();

        // Create some observation criterion comments referencing Q026.
        $obscritcom = new observation_criterion_comment(0, (object) [
            'criterionid' => $q026id,
            'observationid' => 1,
            'comment' => 'Test comment for Q026',
        ]);
        $obscritcom->create();

        // Create a cert_decl referencing Q026.
        $certdecl = new cert_decl(0, (object) [
            'criterionid' => $q026id,
            'planningid' => 1,
            'studentid' => 1,
            'level' => 3,
            'status' => 1,
            'comment' => 'Test comment',
            'commentformat' => 1,
        ]);
        $certdecl->create();

        // Run the task.
        $task = new fix_split_criteria();
        $task->execute();

        // Verify Q025 has the merged label.
        $mergedq025 = criterion::get_record(['id' => $q025id]);
        $this->assertNotNull($mergedq025);
        $expectedlabel = self::Q025_SPLIT_LABEL . ' ' . self::Q026_SPLIT_LABEL;
        $this->assertEquals($expectedlabel, $mergedq025->get('label'));

        // Verify the original Q026 (with split label) no longer exists.
        $remainingq026 = criterion::get_records_select(
            "idnumber = :idnumber AND gridid = :gridid AND " . $this->get_sql_compare_text('label') . " = :label",
            ['idnumber' => 'Q026', 'gridid' => $gridid, 'label' => self::Q026_SPLIT_LABEL]
        );
        $this->assertEmpty($remainingq026);

        // Verify all references have been updated from old Q026 to merged Q025.
        $updatedobslevel = observation_criterion_level::get_record(['id' => $obslevel1->get('id')]);
        $this->assertNotNull($updatedobslevel);
        $this->assertEquals($q025id, $updatedobslevel->get('criterionid'));

        $updatedobscritcom = observation_criterion_comment::get_record(['id' => $obscritcom->get('id')]);
        $this->assertNotNull($updatedobscritcom);
        $this->assertEquals($q025id, $updatedobscritcom->get('criterionid'));

        $updatedcertdecl = cert_decl::get_record(['id' => $certdecl->get('id')]);
        $this->assertNotNull($updatedcertdecl);
        $this->assertEquals($q025id, $updatedcertdecl->get('criterionid'));

        // Verify total criterion count: we started with 2 (Q025, Q026),
        // after merge: 1 criterion.
        $totalcriteria = criterion::count_records(['gridid' => $gridid]);
        $this->assertEquals(1, $totalcriteria);
    }

    /**
     * Test that the task handles grids with only Q025 (no Q026).
     * Uses an arbitrary label that does not match the production label,
     * so the task should not modify it.
     */
    public function test_task_with_only_q025_no_q026(): void {
        // Create a grid with only Q025.
        $grid = new grid(0, (object) [
            'name' => 'Test grid 6',
            'idnumber' => 'TESTGRID6',
            'type' => grid::COMPETVET_CRITERIA_EVALUATION,
        ]);
        $grid->create();
        $gridid = $grid->get('id');

        // Create Q025 criterion with arbitrary label (not matching production).
        $q025 = new criterion(0, (object) [
            'label' => 'Some criterion Q025',
            'idnumber' => 'Q025',
            'parentid' => 0,
            'gridid' => $gridid,
            'sort' => 1,
        ]);
        $q025->create();

        // Run the task.
        $task = new fix_split_criteria();
        $task->execute();

        // Verify Q025 is unchanged (task only matches production labels).
        $unchangedq025 = criterion::get_record(['id' => $q025->get('id')]);
        $this->assertNotNull($unchangedq025);
        $this->assertEquals('Some criterion Q025', $unchangedq025->get('label'));

        // Verify no Q026 was created.
        $this->assertEquals(1, criterion::count_records(['gridid' => $gridid]));
    }

    /**
     * Test that the task is idempotent for Q025/Q026 (running twice produces the same result).
     * Uses production labels so the merge actually happens.
     */
    public function test_task_is_idempotent_q025_q026(): void {
        // Create a grid with Q025 and Q026 using production split labels.
        $grid = new grid(0, (object) [
            'name' => 'Test grid 7',
            'idnumber' => 'TESTGRID7',
            'type' => grid::COMPETVET_CRITERIA_EVALUATION,
        ]);
        $grid->create();
        $gridid = $grid->get('id');

        $q025 = new criterion(0, (object) [
            'label' => self::Q025_SPLIT_LABEL,
            'idnumber' => 'Q025',
            'parentid' => 0,
            'gridid' => $gridid,
            'sort' => 1,
        ]);
        $q025->create();

        $q026 = new criterion(0, (object) [
            'label' => self::Q026_SPLIT_LABEL,
            'idnumber' => 'Q026',
            'parentid' => 0,
            'gridid' => $gridid,
            'sort' => 2,
        ]);
        $q026->create();

        // Run the task first time.
        $task = new fix_split_criteria();
        $task->execute();

        // Get the merged criterion id.
        $mergedq025 = criterion::get_record(['idnumber' => 'Q025', 'gridid' => $gridid]);
        $this->assertNotNull($mergedq025);
        $firstlabel = $mergedq025->get('label');

        // Run the task a second time.
        $task->execute();

        // Verify the result is the same (idempotent).
        $mergedq025after = criterion::get_record(['id' => $mergedq025->get('id')]);
        $this->assertNotNull($mergedq025after);
        $this->assertEquals($firstlabel, $mergedq025after->get('label'));

        // Verify the label was NOT double-merged.
        $this->assertStringNotContainsString('bonnes pratiques bonnes pratiques', $mergedq025after->get('label'));

        // Verify no duplicate Q026 was created.
        $q026count = criterion::count_records(['idnumber' => 'Q026', 'gridid' => $gridid]);
        $this->assertEquals(0, $q026count);
    }

    /**
     * Helper to get sql_compare_text for the current database.
     *
     * @param string $column The database column name.
     * @return string
     */
    private function get_sql_compare_text(string $column): string {
        global $DB;
        return $DB->sql_compare_text($column);
    }
}
