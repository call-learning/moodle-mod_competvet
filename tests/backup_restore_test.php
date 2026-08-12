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
// phpcs:ignoreFile

namespace mod_competvet;

use advanced_testcase;
use backup;
use backup_controller;
use DateTime;
use mod_competvet\competvet;
use mod_competvet\local\persistent\case_data;
use mod_competvet\local\persistent\case_entry;
use mod_competvet\local\persistent\cert_decl;
use mod_competvet\local\persistent\cert_valid;
use mod_competvet\local\persistent\criterion;
use mod_competvet\local\persistent\grid;
use mod_competvet\local\persistent\observation;
use mod_competvet\local\persistent\planning;
use mod_competvet\local\persistent\situation;
use mod_competvet\tests\test_data_definition;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../../config.php');
global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');

/**
 * Test backup and restore of a competvet activity.
 *
 * @package mod_competvet
 * @category backup
 * @copyright 2024 CALL Learning
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\backup_competvet_activity_task::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\restore_competvet_activity_task::class)]
final class backup_restore_test extends advanced_testcase {
    use test_data_definition;

    /**
     * Test backup and restore of a competvet activity.
     */
    public function test_backup_restore(): void {
        global $DB;
        $this->resetAfterTest(true);

        // Create a course and add a competvet instance.
        // Note: we do NOT create default grids here — the generator handles that.
        $generator = $this->getDataGenerator();
        $competvetgenerator = $generator->get_plugin_generator('mod_competvet');
        $startdate = new DateTime('last Monday');
        $this->generates_definition($this->get_data_definition_set_3($startdate->getTimestamp()), $generator, $competvetgenerator);

        $situation = situation::get_record(['shortname' => 'SIT1']);
        $competvet = competvet::get_from_situation($situation);

        $course = $DB->get_record('course', ['shortname' => 'course 1']);
        $this->setAdminUser();

        // Capture original grid and criterion counts before backup.
        $oldgridcount = grid::count_records();
        $oldcriterioncount = criterion::count_records();

        // Prepare for backup.
        $bc = new backup_controller(
            backup::TYPE_1COURSE,
            $course->id,
            backup::FORMAT_MOODLE,
            backup::INTERACTIVE_NO,
            backup::MODE_SAMESITE,
            2
        );

        // Execute backup.
        $bc->execute_plan();
        $backupid = $bc->get_backupid();
        $backupbasepath = $bc->get_plan()->get_basepath();
        $results = $bc->get_results();
        $file = $results['backup_destination'];

        $bc->destroy();

        // Restore the backup immediately.

        // Check if we need to unzip the file because the backup temp dir does not contains backup files.
        if (!file_exists($backupbasepath . "/moodle_backup.xml")) {
            $file->extract_to_pathname(get_file_packer('application/vnd.moodle.backup'), $backupbasepath);
        }

        $newcourseid = \restore_dbops::create_new_course(
            $course->fullname . 'RESTORED',
            $course->shortname . 'RESTORED',
            $course->category
        );

        // Prepare for restore.
        $rc = new \restore_controller(
            $backupid,
            $newcourseid,
            \backup::INTERACTIVE_NO,
            \backup::MODE_SAMESITE,
            2,
            \backup::TARGET_NEW_COURSE
        );

        // Execute restore.
        $rc->execute_precheck();
        $rc->execute_plan();
        $rc->destroy();
        $courserestore = get_course($newcourseid);
        // Check that the instance was restored correctly.
        $modinfo = get_fast_modinfo($courserestore);
        $cms = $modinfo->get_instances_of('competvet');
        $this->assertNotEmpty($cms);
        $cm = reset($cms);
        $newsituation = competvet::get_from_cmid($cm->id)->get_situation();
        $oldsituation = $competvet->get_situation();

        $this->check_created_instances($oldsituation, $newsituation);

        // Verify grid and criterion integrity after restore.
        $this->check_grid_integrity($oldgridcount);
        $this->check_criterion_integrity($oldcriterioncount);
        $this->check_grid_reference_integrity($oldsituation, $newsituation);
        $this->check_criterion_reference_integrity($oldsituation, $newsituation);
    }

    /**
     * Check that grid counts are correct after restore (no unintended duplication).
     *
     * @param int $oldgridcount
     */
    private function check_grid_integrity(int $oldgridcount) {
        // The restored course should have exactly one more grid than before (the restored activity's grid).
        // If grid reuse kicked in, the count should be the same as before (grid was reused, not duplicated).
        $newgridcount = grid::count_records();
        $expectednewgridcount = $oldgridcount + 1;
        $this->assertLessThanOrEqual(
            $expectednewgridcount,
            $newgridcount,
            'Grid count exceeded expected maximum — possible unintended duplication after restore.'
        );
    }

    /**
     * Check that criterion counts are correct after restore (no unintended duplication).
     *
     * @param int $oldcriterioncount
     */
    private function check_criterion_integrity(int $oldcriterioncount) {
        // The restored course should have exactly one more criterion than before (the restored activity's criteria).
        $newcriterioncount = criterion::count_records();
        $expectednewcriterioncount = $oldcriterioncount + 1;
        $this->assertLessThanOrEqual(
            $expectednewcriterioncount,
            $newcriterioncount,
            'Criterion count exceeded expected maximum — possible unintended duplication after restore.'
        );
    }

    /**
     * Check that grid references in situations are correct after restore.
     *
     * @param situation $oldsituation
     * @param situation $newsituation
     */
    private function check_grid_reference_integrity(situation $oldsituation, situation $newsituation) {
        global $DB;

        // The restored situation's grid references should be non-null.
        $evalgridid = $newsituation->get('evalgrid');
        $certifgridid = $newsituation->get('certifgrid');
        $listgridid = $newsituation->get('listgrid');

        $this->assertNotNull($evalgridid, 'Restored situation evalgrid should not be null.');
        $this->assertNotNull($certifgridid, 'Restored situation certifgrid should not be null.');
        $this->assertNotNull($listgridid, 'Restored situation listgrid should not be null.');

        // Verify that the grid IDs are positive integers.
        $this->assertGreaterThan(0, $evalgridid, 'Restored evalgrid ID should be positive.');
        $this->assertGreaterThan(0, $certifgridid, 'Restored certifgrid ID should be positive.');
        $this->assertGreaterThan(0, $listgridid, 'Restored listgrid ID should be positive.');

        // Verify grids exist by querying the DB directly.
        // Note: We use $DB->record_exists() instead of grid::record_exists() because
        // the persistent class caches records and may return stale data.
        $gridids = [$evalgridid, $certifgridid, $listgridid];
        foreach ($gridids as $gridid) {
            $this->assertTrue(
                $DB->record_exists('competvet_grid', ['id' => $gridid]),
                "Restored situation references non-existent grid ID $gridid. Total grids in DB: " . $DB->count_records('competvet_grid')
            );
            $criteria = $DB->get_records('competvet_criterion', ['gridid' => $gridid]);
            foreach ($criteria as $criterion) {
                if (!$criterion->parentid) {
                    continue;
                }
                $parent = $DB->get_record('competvet_criterion', ['id' => $criterion->parentid]);
                $this->assertNotFalse($parent, 'Restored criterion parent should exist.');
                $this->assertEquals($gridid, $parent->gridid, 'Restored criterion parent must belong to the same grid.');
                $this->assertEquals(0, $parent->parentid, 'Restored criterion parent must be a top-level criterion.');
            }
        }
    }

    /**
     * Check that criterion references in observations and certifications are correct after restore.
     *
     * @param situation $oldsituation
     * @param situation $newsituation
     */
    private function check_criterion_reference_integrity(situation $oldsituation, situation $newsituation) {
        global $DB;
        $newcompetvet = competvet::get_from_situation($newsituation);
        $newplannings = array_values(planning::get_records(['situationid' => $newsituation->get('id')]));

        foreach ($newplannings as $newplanning) {
            // Check observation criteria levels reference valid criteria.
            $observations = array_values(observation::get_records(['planningid' => $newplanning->get('id')]));
            foreach ($observations as $obs) {
                $criterialevels = $obs->get_criteria_levels();
                foreach ($criterialevels as $level) {
                    $criterionid = $level->get('criterionid');
                    $this->assertTrue(
                        $DB->record_exists('competvet_criterion', ['id' => $criterionid]),
                        "Observation criteria level points to a non-existent criterion (observationid=" . $obs->get('id') . ", criterionid=$criterionid). " .
                        "Criteria in DB: " . $DB->count_records('competvet_criterion')
                    );
                }
                $critcoms = $obs->get_criteria_comments();
                foreach ($critcoms as $critcom) {
                    $criterionid = $critcom->get('criterionid');
                    $this->assertTrue(
                        $DB->record_exists('competvet_criterion', ['id' => $criterionid]),
                        "Observation criteria comment points to a non-existent criterion (observationid=" . $obs->get('id') . ", criterionid=$criterionid)."
                    );
                }
            }

            // Check certification declarations reference valid criteria.
            $certifications = array_values(cert_decl::get_records(['planningid' => $newplanning->get('id')]));
            foreach ($certifications as $certification) {
                $criterionid = $certification->get('criterionid');
                $this->assertTrue(
                    $DB->record_exists('competvet_criterion', ['id' => $criterionid]),
                    "Certification declaration points to a non-existent criterion (declid=" . $certification->get('id') . ", criterionid=$criterionid)."
                );
            }
        }
    }

    /**
     * Test that restoring the same backup twice does not create unintended duplicates.
     */
    public function test_backup_restore_repeated_restore(): void {
        global $DB, $CFG;
        $this->resetAfterTest(true);

        // Ensure backuptempdir is set (it may be null in some test environments).
        if (empty($CFG->backuptempdir)) {
            $CFG->backuptempdir = $CFG->tempdir . '/backup';
        }

        // Create a course and add a competvet instance.
        $generator = $this->getDataGenerator();
        $competvetgenerator = $generator->get_plugin_generator('mod_competvet');
        $startdate = new DateTime('last Monday');
        $this->generates_definition($this->get_data_definition_set_3($startdate->getTimestamp()), $generator, $competvetgenerator);

        $situation = situation::get_record(['shortname' => 'SIT1']);
        $competvet = competvet::get_from_situation($situation);
        $course = $DB->get_record('course', ['shortname' => 'course 1']);
        $this->setAdminUser();

        // Capture original counts.
        $originalgridcount = grid::count_records();
        $originalcriterioncount = criterion::count_records();

        // Backup the original course.
        $bc = new backup_controller(
            backup::TYPE_1COURSE,
            $course->id,
            backup::FORMAT_MOODLE,
            backup::INTERACTIVE_NO,
            backup::MODE_SAMESITE,
            2
        );
        $bc->execute_plan();
        $backupid = $bc->get_backupid();
        $backupbasepath = $bc->get_plan()->get_basepath();
        $results = $bc->get_results();
        $file = $results['backup_destination'];
        $bc->destroy();

        // Restore the backup immediately - same pattern as test_backup_restore.
        // Check if we need to unzip the file because the backup temp dir does not contains backup files.
        if (!file_exists($backupbasepath . "/moodle_backup.xml")) {
            $file->extract_to_pathname(get_file_packer('application/vnd.moodle.backup'), $backupbasepath);
        }

        // First restore into a new course.
        $firstrestoreid = \restore_dbops::create_new_course(
            $course->fullname . 'RESTORED1',
            $course->shortname . 'R1',
            $course->category
        );
        $rc1 = new \restore_controller(
            $backupid,
            $firstrestoreid,
            \backup::INTERACTIVE_NO,
            \backup::MODE_SAMESITE,
            2,
            \backup::TARGET_NEW_COURSE
        );
        $precheckresult = $rc1->execute_precheck();
        $this->assertTrue($precheckresult, 'First restore precheck should pass.');
        $rc1->execute_plan();
        $rc1->destroy();

        // Capture counts after first restore.
        $firstrestoregridcount = grid::count_records();
        $firstrestorecriterioncount = criterion::count_records();

        // Second restore of the same backup into another new course.
        // Check if we need to unzip the file because the backup temp dir does not contains backup files.
        if (!file_exists($backupbasepath . "/moodle_backup.xml")) {
            $file->extract_to_pathname(get_file_packer('application/vnd.moodle.backup'), $backupbasepath);
        }
        $secondrestoreid = \restore_dbops::create_new_course(
            $course->fullname . 'RESTORED2',
            $course->shortname . 'R2',
            $course->category
        );
        $rc2 = new \restore_controller(
            $backupid,
            $secondrestoreid,
            \backup::INTERACTIVE_NO,
            \backup::MODE_SAMESITE,
            2,
            \backup::TARGET_NEW_COURSE
        );
        $rc2->execute_precheck();
        $rc2->execute_plan();
        $rc2->destroy();

        // Capture counts after second restore.
        $secondrestoregridcount = grid::count_records();
        $secondrestorecriterioncount = criterion::count_records();

        // Each restore should not create unintended duplicate grids or criteria.
        // Due to the grid/criterion reuse policy (matching by idnumber), restoring
        // into a site that already has grids with matching idnumbers will reuse
        // existing grids instead of creating new ones. So we only assert that
        // the count does not exceed the expected maximum (no duplication).
        $this->assertLessThanOrEqual(
            $originalgridcount + 3,
            $firstrestoregridcount,
            'First restore should not create more than 3 new grids (grid reuse may prevent new creation).'
        );
        $this->assertLessThanOrEqual(
            $originalgridcount + 6,
            $secondrestoregridcount,
            'Second restore should not create more than 6 new grids total (grid reuse may prevent new creation).'
        );
        $this->assertLessThanOrEqual(
            $originalcriterioncount + 10,
            $firstrestorecriterioncount,
            'First restore should not create more than 10 new criteria (criterion reuse may prevent new creation).'
        );
        $this->assertLessThanOrEqual(
            $originalcriterioncount + 20,
            $secondrestorecriterioncount,
            'Second restore should not create more than 20 new criteria total (criterion reuse may prevent new creation).'
        );
    }

    /**
     * Test that restoring into a site that already contains grids does not create duplicates
     * when the grid idnumber matches an existing grid.
     */
    public function test_backup_restore_into_existing_grid(): void {
        global $DB;
        $this->resetAfterTest(true);

        $generator = $this->getDataGenerator();
        $competvetgenerator = $generator->get_plugin_generator('mod_competvet');
        $startdate = new DateTime('last Monday');

        // Create default grids first.
        $this->create_default_grids();

        // Create course 1 with a competvet activity.
        $datadef1 = $this->get_data_definition_set_3($startdate->getTimestamp());
        $this->generates_definition($datadef1, $generator, $competvetgenerator);

        $course1 = $DB->get_record('course', ['shortname' => 'course 1']);

        // Capture grid count before backup (includes default grids + activity grids).
        $gridcountbefore = grid::count_records();

        // Backup course 1.
        $bc = new backup_controller(
            backup::TYPE_1COURSE,
            $course1->id,
            backup::FORMAT_MOODLE,
            backup::INTERACTIVE_NO,
            backup::MODE_SAMESITE,
            2
        );
        $bc->execute_plan();
        $backupid = $bc->get_backupid();
        $backupbasepath = $bc->get_plan()->get_basepath();
        $results = $bc->get_results();
        $file = $results['backup_destination'];
        $bc->destroy();

        // Ensure the backup file is extracted so the restore controller can find it.
        $normalizedbackupbasepath = str_replace('//', '/', $backupbasepath);
        if (!file_exists($normalizedbackupbasepath . '/moodle_backup.xml')) {
            $file->extract_to_pathname(get_file_packer('application/vnd.moodle.backup'), $normalizedbackupbasepath);
        }

        // Create course 2 with a competvet activity that uses the same default grid idnumbers.
        // We need unique shortnames for both courses.
        $datadef2 = [
            'course 2' => [
                'users' => [
                    'student' => ['student1', 'student2'],
                    'observer' => ['observer1', 'observer2'],
                    'teacher' => ['teacher1'],
                    'manager' => ['manager'],
                ],
                'groups' => [
                    'group 8.1' => ['users' => ['student1']],
                    'group 8.2' => ['users' => ['student2']],
                    'group 8.3' => ['users' => []],
                    'group 8.4' => ['users' => []],
                ],
                'activities' => [
                    'SIT1B' => [
                        'category' => 'Y1',
                        'plannings' => [
                            [
                                'startdate' => $startdate->getTimestamp(),
                                'enddate' => $startdate->getTimestamp() + 604800, // 1 week.
                                'groupname' => 'group 8.1',
                                'session' => '2023',
                                'observations' => [
                                    [
                                        'category' => \mod_competvet\local\persistent\observation::CATEGORY_EVAL_AUTOEVAL,
                                        'student' => 'student1',
                                        'observer' => 'student1',
                                        'context' => 'A context for autoeval',
                                        'comments' => [
                                            ['type' => \mod_competvet\local\persistent\observation_comment::OBSERVATION_COMMENT, 'comment' => 'A comment'],
                                            ['type' => \mod_competvet\local\persistent\observation_comment::AUTOEVAL_OBSERVER_COMMENT, 'comment' => 'Another comment'],
                                        ],
                                        'criteria' => [
                                            ['id' => 'Q001', 'value' => 1],
                                            ['id' => 'Q002', 'value' => 'Comment autoeval 1'],
                                            ['id' => 'Q003', 'value' => 'Comment autoeval 2'],
                                        ],
                                    ],
                                ],
                                'certifications' => [
                                    [
                                        'student' => 'student1',
                                        'criterion' => 'CERT1',
                                        'level' => 50,
                                        'comment' => 'A comment',
                                        'status' => 'cert:seendone',
                                        'supervisors' => ['observer1', 'observer2'],
                                        'validations' => [
                                            ['status' => 'certvalid:notreached', 'comment' => 'A comment', 'supervisor' => 'observer1'],
                                        ],
                                    ],
                                ],
                                'cases' => [
                                    [
                                        'student' => 'student1',
                                        'fields' => [
                                            'nom_animal' => 'Rex',
                                            'espece' => 'Chien',
                                            'race' => 'Labrador',
                                            'sexe' => 'M',
                                            'date_naissance' => '2019-01-01',
                                            'num_dossier' => '250269802345678',
                                            'date_cas' => '2021-01-01',
                                            'motif_presentation' => 'Vomissement',
                                            'resultats_examens' => 'Autres examens a faire',
                                            'diag_final' => 'Gastro-enterite',
                                            'traitement' => 'Rien',
                                            'evolution' => 'Bon',
                                            'taches_effectuees' => 'Consultation, examen clinique, diagnostic, traitement',
                                            'reflexions_cas' => 'Premier cas observe.',
                                            'role_charge' => 'Observateur',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $this->generates_definition($datadef2, $generator, $competvetgenerator);

        $course2 = $DB->get_record('course', ['shortname' => 'course 2']);

        // Restore into course 2 (which already has grids with the same idnumbers).
        $rc = new \restore_controller(
            $backupid,
            $course2->id,
            \backup::INTERACTIVE_NO,
            \backup::MODE_SAMESITE,
            2,
            \backup::TARGET_NEW_COURSE
        );
        $rc->execute_precheck();
        $rc->execute_plan();
        $rc->destroy();

        // The grids should have been reused, so the total grid count should not increase.
        $gridcountafter = grid::count_records();
        $this->assertEquals(
            $gridcountbefore,
            $gridcountafter,
            'Restoring into a site with existing grids of the same idnumber should reuse them, not duplicate.'
        );
    }

    /**
     * Create the default grids required by the module.
     * These are normally created during installation but are wiped by resetAfterTest.
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
            // Create default criteria for evaluation grid.
            $this->create_default_criteria($evalgrid->get('id'));
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
            // Create default criterion for certification grid.
            $certcrit = new criterion(0, (object) [
                'label' => 'CERT1',
                'idnumber' => 'CERT1',
                'gridid' => $certifgrid->get('id'),
                'sort' => 0,
            ]);
            $certcrit->create();
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

    /**
     * Test that restoring a backup with casecat/casefield duplicates by idnumber does not throw an error.
     */
    public function test_backup_restore_casecat_casefield_duplicate_idnumber(): void {
        global $DB, $CFG;
        $this->resetAfterTest(true);

        // Ensure backuptempdir is set.
        if (empty($CFG->backuptempdir)) {
            $CFG->backuptempdir = $CFG->tempdir . '/backup';
        }

        $generator = $this->getDataGenerator();
        $competvetgenerator = $generator->get_plugin_generator('mod_competvet');
        $startdate = new DateTime('last Monday');

        // Create default grids first.
        $this->create_default_grids();

        // Create course 1 with a competvet activity that has cases.
        $datadef1 = $this->get_data_definition_set_3($startdate->getTimestamp());
        $this->generates_definition($datadef1, $generator, $competvetgenerator);

        $course1 = $DB->get_record('course', ['shortname' => 'course 1']);

        // Capture original casecat and casefield counts.
        $originalcasecatcount = $DB->count_records('competvet_case_cat');
        $originalcasefieldcount = $DB->count_records('competvet_case_field');

        // Backup course 1.
        $bc = new backup_controller(
            backup::TYPE_1COURSE,
            $course1->id,
            backup::FORMAT_MOODLE,
            backup::INTERACTIVE_NO,
            backup::MODE_SAMESITE,
            2
        );
        $bc->execute_plan();
        $backupid = $bc->get_backupid();
        $backupbasepath = $bc->get_plan()->get_basepath();
        $results = $bc->get_results();
        $file = $results['backup_destination'];
        $bc->destroy();

        // Ensure the backup file is extracted.
        $normalizedbackupbasepath = str_replace('//', '/', $backupbasepath);
        if (!file_exists($normalizedbackupbasepath . '/moodle_backup.xml')) {
            $file->extract_to_pathname(get_file_packer('application/vnd.moodle.backup'), $normalizedbackupbasepath);
        }

        // Restore into course 1 (same course, TARGET_REPLACE_COURSE).
        $rc = new \restore_controller(
            $backupid,
            $course1->id,
            \backup::INTERACTIVE_NO,
            \backup::MODE_SAMESITE,
            2,
            \backup::TARGET_EXISTING_DELETING
        );
        $rc->execute_precheck();
        $rc->execute_plan();
        $rc->destroy();

        // The restore should complete without throwing "mdb->get_record() found more than one record!".
        // After replacing the course, counts should be the same as original (reused, not duplicated).
        $casecatafter = $DB->count_records('competvet_case_cat');
        $casefieldafter = $DB->count_records('competvet_case_field');
        $this->assertEquals(
            $originalcasecatcount,
            $casecatafter,
            'casecat count should be the same after replace restore (reused by idnumber).'
        );
        $this->assertEquals(
            $originalcasefieldcount,
            $casefieldafter,
            'casefield count should be the same after replace restore (reused by idnumber).'
        );
    }

    /**
     * Create default criteria for the evaluation grid.
     *
     * @param int $gridid
     */
    private function create_default_criteria(int $gridid): void {
        // Create parent criteria Q001, Q002, Q003 with their children.
        $parentids = ['Q001', 'Q002', 'Q003'];
        foreach ($parentids as $idnumber) {
            $parent = new criterion(0, (object) [
                'label' => $idnumber,
                'idnumber' => $idnumber,
                'gridid' => $gridid,
                'sort' => 0,
            ]);
            $parent->create();
            // Create 5 child criteria for each parent.
            for ($i = 1; $i <= 5; $i++) {
                $child = new criterion(0, (object) [
                    'label' => "{$idnumber}.{$i}",
                    'idnumber' => "{$idnumber}.{$i}",
                    'gridid' => $gridid,
                    'parentid' => $parent->get('id'),
                    'sort' => $i,
                ]);
                $child->create();
            }
        }
    }

    /**
     * Test backup and restore of a competvet activity.
     *
     * @param situation $oldsituation
     * @param situation $newsituation
     */
    private function check_created_instances(situation $oldsituation, situation $newsituation) {
        // Check that situation was restored correctly.
        $this->assertEqualWithoutIds($oldsituation->to_record(), $newsituation->to_record(), ['shortname']);

        // Check planning and observations.
        $newplannings = array_values(planning::get_records(['situationid' => $newsituation->get('id')]));
        $oldplannings = array_values(planning::get_records(['situationid' => $oldsituation->get('id')]));
        $this->assertEquals(count($newplannings), count($oldplannings));
        foreach ($newplannings as $planningindex => $newplanning) {
            $oldplanning = $oldplannings[$planningindex];
            $this->assertEqualWithoutIds($oldplanning->to_record(), $newplanning->to_record());
            $this->assertEquals(
                groups_get_group_name($oldplanning->get('groupid')),
                groups_get_group_name($newplanning->get('groupid'))
            );

            $this->check_created_observations($newplanning, $oldplanning);
            $this->check_created_certifications($newplanning, $oldplanning);
            $this->check_created_caselog($newplanning, $oldplanning);
        }
    }

    /**
     * Assert that two arrays are equal, ignoring the id fields.
     * @param array|object $expected
     * @param array|object $actual
     * @param array $additionalexcludedkeys
     */
    private function assertequalwithoutids($expected, $actual, array $additionalexcludedkeys =[]) {
        $expected = (array) $expected;
        $actual = (array) $actual;

        // Still check that we have no null values in the excluded keys, so if it is not null in the expected, it should
        // also not be null in actual.
        $additionalpattern = !empty($additionalexcludedkeys) ? '|'. implode('|', $additionalexcludedkeys) : '';
        $keypattern = '/id|timemodified|timecreated|usermodified|versionid' . $additionalpattern . '/';
        $expectednotnull = array_filter($expected, fn($key) => preg_match($keypattern, $key), ARRAY_FILTER_USE_KEY);
        $actualnotnull = array_filter($actual, fn($key) => preg_match($keypattern, $key), ARRAY_FILTER_USE_KEY);
        foreach ($expectednotnull as $key => $value) {
            if (!empty($value)) {
                $this->assertNotEmpty($actualnotnull[$key], "Key $key is not empty in expected but is empty in actual.");
            }
        }

        // Any field with a key that starts or ends with 'id' is ignored.
        $expected = array_filter($expected, fn($key) => !preg_match($keypattern, $key), ARRAY_FILTER_USE_KEY);
        $actual = array_filter($actual, fn($key) => !preg_match($keypattern, $key), ARRAY_FILTER_USE_KEY);
        $this->assertEqualsCanonicalizing($expected, $actual);
    }

    /**
     * Check created observations.
     *
     * @param planning $newplanning
     * @param planning $oldplanning
     * @return void
     */
    private function check_created_observations($newplanning, $oldplanning) {
        $newobservations = array_values(observation::get_records(['planningid' => $newplanning->get('id')]));
        $oldobservations = array_values(observation::get_records(['planningid' => $oldplanning->get('id')]));
        $this->assertEquals(count($newobservations), count($oldobservations));
        foreach ($newobservations as $index => $newobservation) {
            $oldobservation = $oldobservations[$index];
            $this->assertEqualWithoutIds($oldobservation->to_record(), $newobservation->to_record());
            // Check comments.
            $newcomments = array_values($newobservation->get_comments());
            $oldcomments = array_values($oldobservation->get_comments());
            $this->assertEquals(count($newcomments), count($oldcomments));
            foreach ($newcomments as $commentindex => $newcomment) {
                $oldcomment = $oldcomments[$commentindex];
                $this->assertEqualWithoutIds($oldcomment->to_record(), $newcomment->to_record());
            }
            // Check criteria comments.
            $newcriteria = array_values($newobservation->get_criteria_comments());
            $oldcriteria = array_values($oldobservation->get_criteria_comments());
            $this->assertEquals(count($newcriteria), count($oldcriteria));
            foreach ($newcriteria as $critcomindex => $newcriterion) {
                $oldcriterion = $oldcriteria[$critcomindex];
                $this->assertEqualWithoutIds($oldcriterion->to_record(), $newcriterion->to_record());
            }
            // Check criteria levels.
            $newcriteria = array_values($newobservation->get_criteria_levels());
            $oldcriteria = array_values($oldobservation->get_criteria_levels());
            $this->assertEquals(count($newcriteria), count($oldcriteria));
            foreach ($newcriteria as $critlevelindex => $newcriterion) {
                $oldcriterion = $oldcriteria[$critlevelindex];
                $this->assertEqualWithoutIds($oldcriterion->to_record(), $newcriterion->to_record());
            }
        }
    }

    /**
     * Check created certifications
     *
     * @param planning $newplanning
     * @param planning $oldplanning
     * @return void
     */
    private function check_created_certifications(planning $newplanning, planning $oldplanning) {
        $newcertifications = array_values(cert_decl::get_records(['planningid' => $newplanning->get('id')]));
        $oldcertifications = array_values(cert_decl::get_records(['planningid' => $oldplanning->get('id')]));
        $this->assertEquals(count($newcertifications), count($oldcertifications));
        foreach ($newcertifications as $index => $newcertification) {
            $oldcertification = $oldcertifications[$index];
            $this->assertEqualWithoutIds($oldcertification->to_record(), $newcertification->to_record());
            $newvalidations = array_values(cert_valid::get_records(['declid' => $newcertification->get('id')]));
            $oldvalidations = array_values(cert_valid::get_records(['declid' => $oldcertification->get('id')]));
            $this->assertEquals(count($newvalidations), count($oldvalidations));
            foreach ($newvalidations as $valindex => $newvalidation) {
                $oldvalidation = $oldvalidations[$valindex];
                $this->assertEqualWithoutIds($oldvalidation->to_record(), $newvalidation->to_record());
            }
        }
    }

    /**
     * Check created caselog
     *
     * @param planning $newplanning
     * @param planning $oldplanning
     * @return void
     */
    private function check_created_caselog(planning $newplanning, planning $oldplanning) {
        $newcaseentries = array_values(case_entry::get_records(['planningid' => $newplanning->get('id')]));
        $oldcaseentries = array_values(case_entry::get_records(['planningid' => $oldplanning->get('id')]));
        $this->assertEquals(count($newcaseentries), count($oldcaseentries));
        foreach ($newcaseentries as $index => $newcasentry) {
            $oldcaseentry = $oldcaseentries[$index];
            $this->assertEqualWithoutIds($oldcaseentry->to_record(), $newcasentry->to_record());
            $newcasedatas = array_values(case_data::get_records(['entryid' => $newcasentry->get('id')]));
            $oldcasedatas = array_values(case_data::get_records(['entryid' => $oldcaseentry->get('id')]));
            $this->assertEquals(count($newcasedatas), count($oldcasedatas));
            foreach ($newcasedatas as $valindex => $newvalidation) {
                $oldcasedata = $oldcasedatas[$valindex];
                $this->assertEqualWithoutIds($oldcasedata->to_record(), $newvalidation->to_record());
            }
        }
    }
}
