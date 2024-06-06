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
use mod_competvet\local\api\certifications;
use mod_competvet\local\api\plannings;
use mod_competvet\local\persistent\cert_decl;
use mod_competvet\local\persistent\cert_valid;
use mod_competvet\local\persistent\criterion;
use mod_competvet\local\persistent\grid;
use mod_competvet\local\persistent\situation;
use test_data_definition;

/**
 * Observations API test
 *
 * @package     mod_competvet
 * @copyright   2023 CALL Learning <contact@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class certifications_test extends advanced_testcase {
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
     * Test the creation of a certification
     */
    public function test_add_certification() {
        $student = core_user::get_user_by_username('student1');
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $plannings = plannings::get_plannings_for_situation_id($situation->get('id'), $student->id);
        $planning = array_shift($plannings);

        $grid = grid::get_record(['type' => grid::COMPETVET_CRITERIA_CERTIFICATION]);
        $criterion = criterion::get_record(['idnumber' => 'CERT1', 'gridid' => $grid->get('id')]);
        // Act: Call the method under test.
        $certificationid = certifications::add_cert_declaration(
            $criterion->get('id'),
            $student->id,
            $planning['id'],
            5,
            'A comment',
            FORMAT_PLAIN,
            cert_decl::STATUS_DECL_SEENDONE
        );

        $certification = cert_decl::get_record(['id' => $certificationid]);
        $this->assertNotNull($certification, "Certification should be created");
        $this->assertEquals($criterion->get('id'), $certification->get('criterionid'), "Criterion id should be set");
        $this->assertEquals($student->id, $certification->get('studentid'), "Student id should be set");
        $this->assertEquals($planning['id'], $certification->get('planningid'), "Planning id should be set");
        $this->assertEquals(5, $certification->get('level'), "Level should be set");
        $this->assertEquals('A comment', $certification->get('comment'), "Comment should be set");
        $this->assertEquals(cert_decl::STATUS_DECL_SEENDONE, $certification->get('status'), "Status should be set");

    }

    /**
     * Test the editing of a certification
     */
    public function test_update_certification() {
        $certification = $this->create_certification();
        // Act: Call the method under test.
        $success = certifications::update_cert_declaration(
            $certification->id,
            3,
            'A new comment',
            FORMAT_PLAIN,
            cert_decl::STATUS_STUDENT_NOTSEEN,
        );

        // Assert: Check that the results are as expected.
        $this->assertTrue($success, "Update certification should return true");
        $certification = cert_decl::get_record(['id' => $certification->id]);
        $this->assertEquals(3, $certification->get('level'), "Level should be updated");
        $this->assertEquals('A new comment', $certification->get('comment'), "Comment should be updated");
        $this->assertEquals(cert_decl::STATUS_STUDENT_NOTSEEN, $certification->get('status'), "Status should be updated");
    }
    /**
     * Test the deletion of a certification
     */
    public function test_delete_certification() {
        $certification = $this->create_certification();
        // Act: Call the method under test.
        $success = certifications::delete_cert_declaration($certification->id);

        // Assert: Check that the results are as expected.
        $this->assertFalse(cert_decl::get_record(['id' => $certification->id]), "Certification was not deleted");
        $this->assertEmpty(cert_decl::get_records(['id' => $certification->id]), "Certification decls were not deleted");
    }

    private function create_certification(): object {
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_competvet');
        $student = core_user::get_user_by_username('student1');
        $observer1 = core_user::get_user_by_username('observer1');
        $observer2 = core_user::get_user_by_username('observer2');
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $plannings = plannings::get_plannings_for_situation_id($situation->get('id'), $student->id);
        $planning = array_shift($plannings);
        $grid = grid::get_record(['type' => grid::COMPETVET_CRITERIA_CERTIFICATION]);
        $criterion = criterion::get_record(['idnumber' => 'CERT1', 'gridid' => $grid->get('id')]);

        $certification = $generator->create_certification(
            [
                'criterionid' => $criterion->get('id'),
                'studentid' => $student->id,
                'planningid' => $planning['id'],
                'level' => 4,
                'comment' => 'A comment',
                'commentformat' => FORMAT_PLAIN,
                'status' => cert_decl::STATUS_DECL_SEENDONE,
                'decls' => [
                    [
                        'supervisorid' => $observer1->id,
                        'comment' => 'Not reached',
                        'status' => cert_valid::STATUS_LEVEL_NOT_REACHED,
                    ],
                    [
                        'supervisorid' => $observer2->id,
                        'comment' => 'A comment',
                        'status' => cert_valid::STATUS_CONFIRMED,
                    ],
                ],
            ]
        );
        return $certification;
    }
    /**
     * Test getting the supervisor invitations for a certification
     */
    public function test_get_certification_supervisors() {
        $certification = $this->create_certification();
        $supervisors = certifications::get_declaration_supervisors($certification->id);

        // Assert: Check that the results are as expected
        $this->assertIsArray($supervisors, "Should return an array of supervisor ids");
        $this->assertCount(2, $supervisors, "Should return 2 supervisor ids");
    }

    /**
     * Test inviting a supervisor to reply on a certification
     */
    public function test_certification_supervisor_invite() {
        // Arrange: Prepare the necessary objects and values
        $declid = 1;
        $supervisorid = 2;

        // Act: Call the method under test
        $success = certifications::declaration_supervisor_invite($declid, $supervisorid);

        // Assert: Check that the results are as expected
        $this->assertTrue($success, "Inviting supervisor should return true");
    }


    /**
     * Test removing the invitation for a supervisor to reply on a certification
     */
    public function test_certification_supervisor_remove() {
        // Arrange: Prepare the necessary objects and values
        $declid = 1;
        $supervisorid = 2;

        // Act: Call the method under test
        $success = certifications::declaration_supervisor_remove($declid, $supervisorid);

        // Assert: Check that the results are as expected
        $this->assertTrue($success, "Removing supervisor should return true");
    }
}
