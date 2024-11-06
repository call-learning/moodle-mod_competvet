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
use mod_competvet\local\persistent\case_data;
use mod_competvet\local\persistent\case_entry;
use mod_competvet\local\persistent\cert_decl;
use mod_competvet\local\persistent\cert_valid;
use mod_competvet\local\persistent\observation;
use mod_competvet\local\persistent\planning;
use mod_competvet\local\persistent\situation;
use test_data_definition;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../../config.php');
global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/mod/competvet/tests/test_data_definition.php');

/**
 * Test backup and restore of a competvet activity.
 *
 * @package mod_competvet
 * @category backup
 * @copyright 2024 CALL Learning
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class backup_restore_test extends advanced_testcase {
    use test_data_definition;

    /**
     * Test backup and restore of a competvet activity.
     * @covers \mod_competvet\backup\backup_competvet_activity_task
     * @covers \mod_competvet\backup\restore_competvet_activity_task
     */
    public function test_backup_restore(): void {
        global $DB;
        $this->resetAfterTest(true);

        // Create a course and add a competvet instance.
        $generator = $this->getDataGenerator();
        $competvetgenerator = $generator->get_plugin_generator('mod_competvet');
        $startdate = new DateTime('last Monday');
        $this->generates_definition($this->get_data_definition_set_3($startdate->getTimestamp()), $generator, $competvetgenerator);

        $situation = situation::get_record(['shortname' => 'SIT1']);
        $competvet = competvet::get_from_situation($situation);

        $course = $DB->get_record('course', ['shortname' => 'course 1']);
        $this->setAdminUser();
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
        $newplannings = planning::get_records(['situationid' => $newsituation->get('id')]);
        $oldplannings = planning::get_records(['situationid' => $oldsituation->get('id')]);
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
        $keypattern = '/id|timemodified|timecreated|usermodified' . $additionalpattern . '/';
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
        $newobservations = observation::get_records(['planningid' => $newplanning->get('id')]);
        $oldobservations = observation::get_records(['planningid' => $oldplanning->get('id')]);
        $this->assertEquals(count($newobservations), count($oldobservations));
        foreach ($newobservations as $index => $newobservation) {
            $oldobservation = $oldobservations[$index];
            $this->assertEqualWithoutIds($oldobservation->to_record(), $newobservation->to_record());
            // Check comments.
            $newcomments = $newobservation->get_comments();
            $oldcomments = $oldobservation->get_comments();
            $this->assertEquals(count($newcomments), count($oldcomments));
            foreach ($newcomments as $commentindex => $newcomment) {
                $oldcomment = $oldcomments[$commentindex];
                $this->assertEqualWithoutIds($oldcomment->to_record(), $newcomment->to_record());
            }
            // Check criteria comments.
            $newcriteria = $newobservation->get_criteria_comments();
            $oldcriteria = $oldobservation->get_criteria_comments();
            $this->assertEquals(count($newcriteria), count($oldcriteria));
            foreach ($newcriteria as $critcomindex => $newcriterion) {
                $oldcriterion = $oldcriteria[$critcomindex];
                $this->assertEqualWithoutIds($oldcriterion->to_record(), $newcriterion->to_record());
            }
            // Check criteria levels.
            $newcriteria = $newobservation->get_criteria_levels();
            $oldcriteria = $oldobservation->get_criteria_levels();
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
        $newcertifications = cert_decl::get_records(['planningid' => $newplanning->get('id')]);
        $oldcertifications = cert_decl::get_records(['planningid' => $oldplanning->get('id')]);
        $this->assertEquals(count($newcertifications), count($oldcertifications));
        foreach ($newcertifications as $index => $newcertification) {
            $oldcertification = $oldcertifications[$index];
            $this->assertEqualWithoutIds($oldcertification->to_record(), $newcertification->to_record());
            $newvalidations = cert_valid::get_records(['declid' => $newcertification->get('id')]);
            $oldvalidations = cert_valid::get_records(['declid' => $oldcertification->get('id')]);
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
        $newcaseentries = case_entry::get_records(['planningid' => $newplanning->get('id')]);
        $oldcaseentries = case_entry::get_records(['planningid' => $oldplanning->get('id')]);
        $this->assertEquals(count($newcaseentries), count($oldcaseentries));
        foreach ($newcaseentries as $index => $newcasentry) {
            $oldcaseentry = $oldcaseentries[$index];
            $this->assertEqualWithoutIds($oldcaseentry->to_record(), $newcasentry->to_record());
            $newcasedatas = case_data::get_records(['entryid' => $newcasentry->get('id')]);
            $oldcasedatas = case_data::get_records(['entryid' => $oldcaseentry->get('id')]);
            $this->assertEquals(count($newcasedatas), count($oldcasedatas));
            foreach ($newcasedatas as $valindex => $newvalidation) {
                $oldcasedata = $oldcasedatas[$valindex];
                $this->assertEqualWithoutIds($oldcasedata->to_record(), $newvalidation->to_record());
            }
        }
    }
}
