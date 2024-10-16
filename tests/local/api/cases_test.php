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
namespace local\api;
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/mod/competvet/tests/test_data_definition.php');

use advanced_testcase;
use core_user;
use mod_competvet\local\api\cases;
use mod_competvet\local\api\plannings;
use mod_competvet\local\persistent\case_field;
use mod_competvet\local\persistent\situation;
use test_data_definition;

/**
 * Case API test
 *
 * @package     mod_competvet
 * @copyright   2023 CALL Learning <contact@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cases_test extends advanced_testcase {
    use test_data_definition;

    /**
     * Setup the test
     *
     * @return void
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser(); // Needed for report builder to work.
        $this->prepare_scenario('set_2');
    }

    /**
     * Test get_entry
     *
     * @return void
     * @covers \mod_competvet\local\api\cases::get_entries
     */
    public function test_get_entry(): void {
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $student = core_user::get_user_by_username('student1');
        $plannings = plannings::get_plannings_for_situation_id($situation->get('id'), $student->id);
        $planning = array_shift($plannings);
        $entries = cases::get_entries($planning['id'], $student->id);
        foreach ($entries->cases as $e) {
            $this->assertEquals($student->id, $e->studentid);
            $this->assertEquals($planning['id'], $e->planningid);
        }
        // Get the first entry and check a couple of fields.
        $firstcase = $entries->cases[0];
        foreach ($firstcase->categories as $category) {
            $this->assertNotEmpty($category->name);
            foreach ($category->fields as $field) {
                $this->assertNotEmpty($field->name);
                switch ($field->idnumber) {
                    case 'date_cas':
                        $this->assertEquals('1 January 2021', $field->displayvalue);
                        break;
                    case 'role_charge':
                        $this->assertEquals('Observateur', $field->displayvalue);
                        break;
                    case 'reflexions_cas':
                        $this->assertEquals('Premier cas observé. Bonne prise en charge.', $field->displayvalue);
                        break;
                }
            }
        }
    }


    /**
     * Test update_case
     *
     * @return void
     * @covers \mod_competvet\local\api\cases::update_case
     */
    public function test_update_case(): void {
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $student = core_user::get_user_by_username('student1');
        $plannings = plannings::get_plannings_for_situation_id($situation->get('id'), $student->id);
        $planning = array_shift($plannings);
        $entries = cases::get_entries($planning['id'], $student->id);

        $case = $entries->cases[0];
        $caseid = $case->id;
        $datecasfield = case_field::get_record(['idnumber' => 'date_cas']);
        $rolechargefield = case_field::get_record(['idnumber' => 'role_charge']);
        $newdata = [$datecasfield->get('id') => '01 January 2023', $rolechargefield->get('id') => 2];
        // Adjust based on actual fields.

        // Perform update.
        $this->setAdminUser();
        cases::update_case($caseid, $newdata);

        $updatedcase = cases::get_entry($caseid);
        foreach ($updatedcase->categories as $category) {
            $this->assertNotEmpty($category->name);
            foreach ($category->fields as $field) {
                $this->assertNotEmpty($field->name);
                switch ($field->idnumber) {
                    case 'date_cas':
                        $this->assertEquals('1 January 2023', $field->displayvalue);
                        break;
                    case 'role_charge':
                        $this->assertEquals('Principal acteur (responsable du cas)', $field->displayvalue);
                        break;
                }
            }
        }
    }
    /**
     * Test delete_case
     *
     * @return void
     * @covers \mod_competvet\local\api\cases::delete_case
     */
    public function test_delete_case(): void {
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $student = core_user::get_user_by_username('student1');
        $plannings = plannings::get_plannings_for_situation_id($situation->get('id'), $student->id);
        $planning = array_shift($plannings);
        $entries = cases::get_entries($planning['id'], $student->id);

        $case = $entries->cases[0];
        $caseid = $case->id;

        // Perform delete.
        $this->setAdminUser();
        $result = cases::delete_case($caseid);

        // Assertions.
        $this->assertTrue($result, 'Case deletion failed.');

        $this->expectException(\moodle_exception::class);
        cases::get_entry($caseid);
    }

    /**
     * Test get_case_list
     *
     * @return void
     * @covers \mod_competvet\local\api\cases::get_case_list
     */
    public function test_get_case_list(): void {
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $student = core_user::get_user_by_username('student1');
        $plannings = plannings::get_plannings_for_situation_id($situation->get('id'), $student->id);
        $planning = array_shift($plannings);
        $caselist = cases::get_case_list($planning['id'], $student->id);

        // Assertions.
        $this->assertNotEmpty($caselist, 'Case list is empty.');

        $expectedcases = [
            [
                'date' => 1609430400,
                'label' => 'Vomissement',
            ],
            [
                'date' => 1686326400,
                'label' => 'Vomissement',
            ],
        ];

        foreach ($caselist as $index => $case) {
            $this->assertEquals($expectedcases[$index]['date'], $case['date'], 'Case date does not match.');
            $this->assertEquals($expectedcases[$index]['label'], $case['label'], 'Case label does not match.');
        }
    }
}
