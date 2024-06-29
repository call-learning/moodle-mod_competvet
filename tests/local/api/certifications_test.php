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
use mod_competvet\local\persistent\criterion;
use mod_competvet\local\persistent\grid;
use mod_competvet\local\persistent\situation;
use test_data_definition;

/**
 * Certification API test
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
     * @covers certifications::add_cert_declaration
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
     * @covers certifications::update_cert_declaration
     */
    public function test_update_certification() {
        $certification = $this->get_certification_declaration();
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
     * Utility function to create a certification
     *
     * @return object
     */
    private function get_certification_declaration(): object {
        $decls = cert_decl::get_records();
        return (array_shift($decls))->to_record();
    }

    /**
     * Test the deletion of a certification
     * @covers certifications::delete_cert_declaration
     */
    public function test_delete_certification() {
        $certification = $this->get_certification_declaration();
        // Act: Call the method under test.
        $success = certifications::delete_cert_declaration($certification->id);

        // Assert: Check that the results are as expected.
        $this->assertFalse(cert_decl::get_record(['id' => $certification->id]), "Certification was not deleted");
        $this->assertEmpty(cert_decl::get_records(['id' => $certification->id]), "Certification decls were not deleted");
    }

    /**
     * Test getting the supervisor invitations for a certification
     * @covers certifications::get_declaration_supervisors
     */
    public function test_get_certification_supervisors() {
        $certification = $this->get_certification_declaration();
        $supervisors = certifications::get_declaration_supervisors($certification->id);

        // Assert: Check that the results are as expected
        $this->assertIsArray($supervisors, "Should return an array of supervisor ids");
        $this->assertCount(2, $supervisors, "Should return 2 supervisor ids");
    }

    /**
     * Test inviting a supervisor to reply on a certification
     * @covers certifications::declaration_supervisor_invite
     */
    public function test_certification_supervisor_invite() {
        $certification = $this->get_certification_declaration();

        $observer3 = $this->getDataGenerator()->create_user();
        // Act: Call the method under test
        $this->assertNotNull(certifications::declaration_supervisor_invite(
            $certification->id,
            $observer3->id
        ));

        $this->assertContainsEquals($observer3->id, certifications::get_declaration_supervisors($certification->id));
    }

    /**
     * Test removing the invitation for a supervisor to reply on a certification
     * @covers certifications::declaration_supervisor_remove
     */
    public function test_certification_supervisor_remove() {
        $certification = $this->get_certification_declaration();

        $observer2 = core_user::get_user_by_username('observer2');

        // Act: Call the method under test.
        $success = certifications::declaration_supervisor_remove($certification->id, $observer2->id);

        // Assert: Check that the results are as expected
        $this->assertTrue($success, "Removing supervisor should return true");
        $this->assertNotContainsEquals($observer2->id, certifications::get_declaration_supervisors($certification->id));
    }

    // Test fetching a certification by ID

    /**
     * Test fetching a certification by ID
     *
     * @return void
     * @covers certifications::get_certification
     */
    public function test_get_certification_by_id() {
        // Create a certification
        $cert = $this->get_certification_declaration();
        // Fetch the certification using the API
        $certification = certifications::get_certification($cert->id);

        // Assertions
        $this->assertNotEmpty($certification);
        $grid = grid::get_record(['type' => grid::COMPETVET_CRITERIA_CERTIFICATION]);
        $criterion = criterion::get_record(['idnumber' => 'CERT1', 'gridid' => $grid->get('id')]);
        $this->assertEquals($criterion->get('label'), $certification['label']);
    }

    /**
     * Test fetching all certifications
     *
     * @return void
     * @covers certifications::get_certifications
     */
    public function test_get_certifications() {
        $this->get_certification_declaration();

        $student = core_user::get_user_by_username('student1');
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $plannings = plannings::get_plannings_for_situation_id($situation->get('id'), $student->id);
        $planning = array_shift($plannings);

        // Fetch all certifications using the API
        $certifications = certifications::get_certifications($planning['id']);

        $this->assertCount(5, $certifications);
        $this->assertCount(1, array_filter($certifications, fn($cert) => $cert['isdeclared']));
    }
}
