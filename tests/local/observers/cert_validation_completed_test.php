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

namespace mod_competvet\local\observers;

use advanced_testcase;
use core_user;
use mod_competvet\local\api\certifications;
use mod_competvet\local\api\plannings;
use mod_competvet\local\persistent\cert_decl;
use mod_competvet\local\persistent\cert_valid;
use mod_competvet\local\persistent\criterion;
use mod_competvet\local\persistent\grid;
use mod_competvet\local\persistent\situation;
use mod_competvet\local\persistent\todo;
use mod_competvet\tests\test_data_definition;
use PHPUnit\Framework\Attributes\CoversMethod;

/**
 * Cert request API test
 *
 * @package     mod_competvet
 * @copyright   2023 CALL Learning <contact@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversMethod(certification_observer::class, 'remove_validation_certifications_todo')]
final class cert_validation_completed_test extends advanced_testcase {
    use test_data_definition;

    /**
     * @var int $declid
     */
    protected $declid;

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
        $this->set_current_date();
        $student = core_user::get_user_by_username('student1');
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $plannings = plannings::get_plannings_for_situation_id($situation->get('id'), $student->id);
        $planninginfo = array_shift($plannings);
        $grid = grid::get_record(['type' => grid::COMPETVET_CRITERIA_CERTIFICATION]);
        $criterion = criterion::get_record(['idnumber' => 'CERT1', 'gridid' => $grid->get('id')]);
        // Act: Call the method under test.
        $this->declid = certifications::add_cert_declaration(
            $criterion->get('id'),
            $student->id,
            $planninginfo['id'],
            5,
            'A comment',
            FORMAT_PLAIN,
            cert_decl::STATUS_DECL_SEENDONE
        );
    }

    /**
     * Test that sending an certification request event will actually create a todo.
     *
     * @return void
     */
    public function test_request_cert_validation_completed_removes_todo(): void {
        $student = core_user::get_user_by_username('student1');
        $observer1 = core_user::get_user_by_username('observer1');
        $observer2 = core_user::get_user_by_username('observer2');
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $plannings = plannings::get_plannings_for_situation_id($situation->get('id'), $student->id);
        $planninginfo = array_shift($plannings);
        certifications::declaration_supervisor_invite($this->declid, $observer1->id, $student->id);
        certifications::declaration_supervisor_invite($this->declid, $observer2->id, $student->id);
        // Check that a todo has been created.
        $this->assertEquals(2, todo::count_records(['status' => todo::STATUS_PENDING]));
        certifications::validate_cert_declaration($this->declid, $observer1->id, cert_valid::STATUS_CONFIRMED, '', FORMAT_PLAIN);
        $this->assertEquals(0, todo::count_records(['status' => todo::STATUS_PENDING]));
    }

    /**
     * A rejecting validation keeps the declaration pending and reopens its todos.
     */
    public function test_rejected_certification_is_pending_and_actionable(): void {
        $student = core_user::get_user_by_username('student1');
        $observer1 = core_user::get_user_by_username('observer1');
        $observer2 = core_user::get_user_by_username('observer2');
        certifications::declaration_supervisor_invite($this->declid, $observer1->id, $student->id);
        certifications::declaration_supervisor_invite($this->declid, $observer2->id, $student->id);

        certifications::validate_cert_declaration(
            $this->declid,
            $observer1->id,
            cert_valid::STATUS_LEVEL_NOT_REACHED,
            '',
            FORMAT_PLAIN
        );

        $certification = certifications::get_certification($this->declid);
        $this->assertFalse($certification['confirmed']);
        $this->assertTrue($certification['rejected']);
        $this->assertSame(2, todo::count_records(['status' => todo::STATUS_PENDING]));
    }

    /**
     * A later confirming validation can complete a previously rejected declaration.
     */
    public function test_rejected_certification_can_be_revalidated(): void {
        $student = core_user::get_user_by_username('student1');
        $observer1 = core_user::get_user_by_username('observer1');
        certifications::declaration_supervisor_invite($this->declid, $observer1->id, $student->id);

        certifications::validate_cert_declaration(
            $this->declid,
            $observer1->id,
            cert_valid::STATUS_LEVEL_NOT_REACHED,
            '',
            FORMAT_PLAIN
        );
        certifications::validate_cert_declaration(
            $this->declid,
            $observer1->id,
            cert_valid::STATUS_CONFIRMED,
            '',
            FORMAT_PLAIN
        );

        $certification = certifications::get_certification($this->declid);
        $this->assertTrue($certification['confirmed']);
        $this->assertFalse($certification['rejected']);
        $this->assertEquals(0, todo::count_records(['status' => todo::STATUS_PENDING]));
    }
}
