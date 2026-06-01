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

namespace mod_competvet;

use advanced_testcase;
use context_module;
use mod_competvet\local\persistent\criterion;
use mod_competvet\local\persistent\grid;
use mod_competvet\local\persistent\observation;
use mod_competvet\local\persistent\observation_criterion_comment;
use mod_competvet\local\persistent\observation_criterion_level;
use mod_competvet\local\persistent\situation;
use mod_competvet\tests\test_data_definition;

/**
 * Utils Tests
 *
 * @package     mod_competvet
 * @copyright   2026 CALL Learning <contact@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\mod_competvet\utils::class)]
final class utils_test extends advanced_testcase {
    use test_data_definition;

    /**
     * Set up the test
     *
     * @return void
     */
    public function setUp(): void {
        global $CFG;
        parent::setUp();
        require_once($CFG->dirroot . '/mod/competvet/lib.php');
        $this->resetAfterTest();
    }

    /**
     * Test get_groups_with_members method
     *
     * @return void
     */
    public function test_get_groups_with_members(): void {
        $this->prepare_scenario('set_2');
        $situations = situation::get_records();
        $situation = reset($situations);
        $competvet = competvet::get_from_situation_id($situation->get('id'));
        $cm = $competvet->get_course_module();

        $groups = utils::get_groups_with_members($cm->id);

        // The scenario creates groups, so we just check that groups are returned.
        $this->assertIsArray($groups);
        $this->assertNotEmpty($groups);

        // Verify that each group has the expected properties.
        foreach ($groups as $group) {
            $this->assertObjectHasProperty('id', $group);
            $this->assertObjectHasProperty('name', $group);
            $this->assertObjectHasProperty('members', $group);
        }
    }

    /**
     * Test get_groups_with_members returns false when no groups exist
     *
     * @return void
     */
    public function test_get_groups_with_members_no_groups(): void {
        // Create a fresh course without using prepare_scenario to avoid pre-created groups.
        $course = $this->getDataGenerator()->create_course();
        $competvetgenerator = $this->getDataGenerator()->get_plugin_generator('mod_competvet');
        $competvet = $competvetgenerator->create_instance(['course' => $course->id]);
        $cm = get_coursemodule_from_instance('competvet', $competvet->id);

        $groups = utils::get_groups_with_members($cm->id);

        $this->assertFalse($groups);
    }

    /**
     * Test page_requirements method
     *
     * @return void
     */
    public function test_page_requirements(): void {
        global $PAGE;

        $this->prepare_scenario('set_2');
        $situations = situation::get_records();
        $situation = reset($situations);
        $competvet = competvet::get_from_situation_id($situation->get('id'));
        $cm = $competvet->get_course_module();

        // Set required parameters.
        $_GET['id'] = $cm->id;

        $this->setAdminUser();

        [$returnedcm, $course, $moduleinstance] = utils::page_requirements('view');

        $this->assertEquals($cm->id, $returnedcm->id);
        $this->assertEquals($competvet->get_course()->id, $course->id);
        $this->assertEquals($competvet->get_instance()->id, $moduleinstance->id);

        // Clean up.
        unset($_GET['id']);
    }

    /**
     * Test split_properties_from_persistent method
     *
     * @return void
     */
    public function test_split_properties_from_persistent(): void {
        $record = (object) [
            'idnumber' => 'GRID001',
            'name' => 'Test Grid',
            'type' => grid::COMPETVET_CRITERIA_EVALUATION,
            'sortorder' => 1,
            'extraproperty' => 'extra value',
            'anotherprop' => 'another value',
        ];

        $result = utils::split_properties_from_persistent(grid::class, $record);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('persistent', $result);
        $this->assertArrayHasKey('otherproperties', $result);

        $persistent = $result['persistent'];
        $this->assertObjectHasProperty('idnumber', $persistent);
        $this->assertObjectHasProperty('name', $persistent);
        $this->assertObjectHasProperty('type', $persistent);
        $this->assertObjectHasProperty('sortorder', $persistent);

        $otherproperties = $result['otherproperties'];
        $this->assertObjectHasProperty('extraproperty', $otherproperties);
        $this->assertObjectHasProperty('anotherprop', $otherproperties);
        $this->assertEquals('extra value', $otherproperties->extraproperty);
        $this->assertEquals('another value', $otherproperties->anotherprop);
    }

    /**
     * Test get_persistent_fields_without_internals method
     *
     * @return void
     */
    public function test_get_persistent_fields_without_internals(): void {
        $fields = utils::get_persistent_fields_without_internals(grid::class);

        $this->assertIsArray($fields);
        $this->assertArrayHasKey('idnumber', $fields);
        $this->assertArrayHasKey('name', $fields);
        $this->assertArrayHasKey('type', $fields);
        $this->assertArrayHasKey('sortorder', $fields);

        // Internal fields should not be present.
        $this->assertArrayNotHasKey('id', $fields);
        $this->assertArrayNotHasKey('timecreated', $fields);
        $this->assertArrayNotHasKey('timemodified', $fields);
        $this->assertArrayNotHasKey('usermodified', $fields);
    }

    /**
     * Test get_persistent_fields_without_internals with custom fields to remove
     *
     * @return void
     */
    public function test_get_persistent_fields_without_internals_custom(): void {
        $fields = utils::get_persistent_fields_without_internals(grid::class, ['id', 'sortorder']);

        $this->assertIsArray($fields);
        $this->assertArrayHasKey('idnumber', $fields);
        $this->assertArrayHasKey('name', $fields);
        $this->assertArrayHasKey('type', $fields);

        // Custom removed field should not be present.
        $this->assertArrayNotHasKey('sortorder', $fields);
        $this->assertArrayNotHasKey('id', $fields);

        // Default internal fields might still be present.
        $this->assertArrayHasKey('timecreated', $fields);
    }

    /**
     * Test is_student method
     *
     * @return void
     */
    public function test_is_student(): void {
        $this->prepare_scenario('set_2');
        $situations = situation::get_records();
        $situation = reset($situations);
        $competvet = competvet::get_from_situation_id($situation->get('id'));
        $context = $competvet->get_context();

        // Create a student user and enrol them.
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $competvet->get_course()->id, 'student');

        $isstudent = utils::is_student($student->id, $context->id);

        $this->assertTrue($isstudent);
    }

    /**
     * Test is_student method with non-student user
     *
     * @return void
     */
    public function test_is_student_non_student(): void {
        $this->prepare_scenario('set_2');
        $situations = situation::get_records();
        $situation = reset($situations);
        $competvet = competvet::get_from_situation_id($situation->get('id'));
        $context = $competvet->get_context();

        // Create a teacher user and enrol them.
        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher->id, $competvet->get_course()->id, 'editingteacher');

        $isstudent = utils::is_student($teacher->id, $context->id);

        $this->assertFalse($isstudent);
    }

    /**
     * Test get_student_roles_id method
     *
     * @return void
     */
    public function test_get_student_roles_id(): void {
        $studentroles = utils::get_student_roles_id();

        $this->assertIsArray($studentroles);
        $this->assertNotEmpty($studentroles);

        // Verify that the returned IDs are valid role IDs.
        foreach ($studentroles as $roleid) {
            $this->assertIsInt($roleid);
            $this->assertGreaterThan(0, $roleid);
        }
    }

    /**
     * Test get_user_info method
     *
     * @return void
     */
    public function test_get_user_info(): void {
        $user = $this->getDataGenerator()->create_user([
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => 'john.doe@example.com',
        ]);

        $userinfo = utils::get_user_info($user->id);

        $this->assertIsArray($userinfo);
        $this->assertArrayHasKey('id', $userinfo);
        $this->assertArrayHasKey('fullname', $userinfo);
        $this->assertArrayHasKey('email', $userinfo);
        $this->assertArrayHasKey('userpictureurl', $userinfo);
        $this->assertArrayHasKey('firstname', $userinfo);
        $this->assertArrayHasKey('lastname', $userinfo);

        $this->assertEquals($user->id, $userinfo['id']);
        $this->assertEquals('John Doe', $userinfo['fullname']);
        $this->assertEquals('john.doe@example.com', $userinfo['email']);
        $this->assertEquals('John', $userinfo['firstname']);
        $this->assertEquals('Doe', $userinfo['lastname']);
        $this->assertNotEmpty($userinfo['userpictureurl']);
    }

    /**
     * Test get_user_info method with non-existent user
     *
     * @return void
     */
    public function test_get_user_info_nonexistent(): void {
        $userinfo = utils::get_user_info(999999);

        $this->assertIsArray($userinfo);
        $this->assertEquals(999999, $userinfo['id']);
        // The fullname will be a language string placeholder when user is not found.
        $this->assertNotEmpty($userinfo['fullname']);
        $this->assertNotEmpty($userinfo['userpictureurl']);
    }

    /**
     * Test user_exists method
     *
     * @return void
     */
    public function test_user_exists(): void {
        $user = $this->getDataGenerator()->create_user();

        $exists = utils::user_exists($user->id);

        $this->assertTrue($exists);
    }

    /**
     * Test user_exists method with non-existent user
     *
     * @return void
     */
    public function test_user_exists_nonexistent(): void {
        $exists = utils::user_exists(999999);

        $this->assertFalse($exists);
    }

    /**
     * Test user_exists method with suspended user
     *
     * @return void
     */
    public function test_user_exists_suspended(): void {
        $user = $this->getDataGenerator()->create_user(['suspended' => 1]);

        // Should return false when checking for active users (default).
        $existsactive = utils::user_exists($user->id, true);
        $this->assertFalse($existsactive);

        // Should return true when not checking for active status.
        $existsany = utils::user_exists($user->id, false);
        $this->assertTrue($existsany);
    }

    /**
     * Test get_users_with_role method
     *
     * @return void
     */
    public function test_get_users_with_role(): void {
        $this->prepare_scenario('set_2');
        $situations = situation::get_records();
        $situation = reset($situations);
        $competvet = competvet::get_from_situation_id($situation->get('id'));

        // Create users and assign roles.
        $teacher1 = $this->getDataGenerator()->create_user();
        $teacher2 = $this->getDataGenerator()->create_user();
        $student = $this->getDataGenerator()->create_user();

        $this->getDataGenerator()->enrol_user($teacher1->id, $competvet->get_course()->id, 'editingteacher');
        $this->getDataGenerator()->enrol_user($teacher2->id, $competvet->get_course()->id, 'editingteacher');
        $this->getDataGenerator()->enrol_user($student->id, $competvet->get_course()->id, 'student');

        $teachers = utils::get_users_with_role('editingteacher', $situation->get('id'));

        $this->assertIsArray($teachers);
        $this->assertCount(2, $teachers);

        // Verify teachers are in the result.
        $teacherids = array_column($teachers, 'id');
        $this->assertContains($teacher1->id, $teacherids);
        $this->assertContains($teacher2->id, $teacherids);
        $this->assertNotContains($student->id, $teacherids);
    }

    /**
     * Test get_users_with_role with non-existent role
     *
     * @return void
     */
    public function test_get_users_with_role_nonexistent(): void {
        $this->prepare_scenario('set_2');
        $situations = situation::get_records();
        $situation = reset($situations);

        // Use a role shortname that definitely doesn't exist.
        $users = utils::get_users_with_role('thisroledoesnotexist123456', $situation->get('id'));

        $this->assertIsArray($users);
        $this->assertEmpty($users);
    }

    /**
     * Test get_grid_usage_count method
     *
     * @return void
     */
    public function test_get_grid_usage_count(): void {
        // Create a simple test setup without using prepare_scenario to avoid complex data.
        $course = $this->getDataGenerator()->create_course();
        $competvetgenerator = $this->getDataGenerator()->get_plugin_generator('mod_competvet');
        $competvet = $competvetgenerator->create_instance(['course' => $course->id]);

        // Create a grid.
        $grid = new grid(0, (object) [
            'name' => 'Test Grid',
            'idnumber' => 'TESTGRID001',
            'type' => grid::COMPETVET_CRITERIA_EVALUATION,
        ]);
        $grid->create();
        $this->assertFalse(utils::is_grid_used($grid));
        // Now assign a grid to a situation and check the grid is used.
        $situation = situation::get_record(['competvetid' => $competvet->id]);
        $situation->set('evalgrid', $grid->get('id'));
        $situation->update();
        $this->assertTrue(utils::is_grid_used($grid));

        // Now assign the grid to the wrong field and check that it does count it as used.
        $situation = situation::get_record(['competvetid' => $competvet->id]);
        $situation->set('evalgrid', 0);
        $situation->set('certifgrid', $grid->get('id'));
        $situation->update();
        $this->assertFalse(utils::is_grid_used($grid));

        // But if we create a criterion and an eval linked to the grid, it should be counted as used.
        $criterion = new criterion(0, (object) [
            'gridid' => $grid->get('id'),
            'label' => 'Test Criterion',
            'idnumber' => 'TEST001',
            'parentid' => 0,
            'sort' => 1,
        ]);
        $criterion->create();
        $this->assertFalse(utils::is_grid_used($grid));
        $observation = new observation(0, (object) [
            'situationid' => 0,
            'observerid' => 0,
            'observedid' => 0,
            'planningid' => 0,
            'studentid' => 0,
            'timeobserved' => time(),
        ]);
        $observation->create();
        $observationcriterioncomment = new observation_criterion_comment(0, (object) [
            'criterionid' => $criterion->get('id'),
            'comment' => 'Test comment',
            'observationid' => $observation->get('id'),
        ]);
        $observationcriterioncomment->create();
        $this->assertTrue(utils::is_grid_used($grid));
    }

    /**
     * Test get_criterion_usage_count method
     *
     * @return void
     */
    public function test_get_criterion_usage_count(): void {
        // Create a simple test setup.
        $course = $this->getDataGenerator()->create_course();
        $competvetgenerator = $this->getDataGenerator()->get_plugin_generator('mod_competvet');
        $competvet = $competvetgenerator->create_instance(['course' => $course->id]);

        // Create a grid and a criterion.
        $grid = new grid(0, (object) [
            'name' => 'Test Grid',
            'idnumber' => 'TESTGRID002',
            'type' => grid::COMPETVET_CRITERIA_EVALUATION,
        ]);
        $grid->create();

        $criterion = new criterion(0, (object) [
            'gridid' => $grid->get('id'),
            'label' => 'Test Criterion',
            'idnumber' => 'TEST001',
            'parentid' => 0,
            'sort' => 1,
        ]);
        $criterion->create();

        $observation = new observation(0, (object) [
            'situationid' => 0,
            'observerid' => 0,
            'observedid' => 0,
            'planningid' => 0,
            'studentid' => 0,
            'timeobserved' => time(),
        ]);
        $observation->create();
        $observationcriterioncomment = new observation_criterion_comment(0, (object) [
            'criterionid' => $criterion->get('id'),
            'comment' => 'Test comment',
            'observationid' => $observation->get('id'),
        ]);
        $observationcriterioncomment->create();
        $observationcriterionlevel = new observation_criterion_level(
            0,
            (object) [
                'criterionid' => $criterion->get('id'),
                'level' => 2,
                'observationid' => $observation->get('id'),
            ]
        );
        $observationcriterionlevel->create();
        $certdecl = new \mod_competvet\local\persistent\cert_decl(0, (object) [
            'name' => 'Test Cert Decl',
            'situations' => [$criterion->get('id')],
            'planningid' => 0,
            'studentid' => 0,
            'status' => \mod_competvet\local\persistent\cert_decl::STATUS_DECL_SEENDONE,
            'comment' => 'Test comment',
            'level' => 2,
            'commentformat' => FORMAT_PLAIN,
            'criterionid' => $criterion->get('id'),
            'supervisorid' => 0,
        ]);
        $certdecl->create();
        $certdeclasso = new \mod_competvet\local\persistent\cert_decl_asso(0, (object) [
            'supervisorid' => $criterion->get('id'),
            'declid' => $certdecl->get('id'),
        ]);
        $certdeclasso->create();
        $this->assertTrue(utils::is_criterion_used($criterion));
        $this->assertTrue(utils::is_grid_used($grid));

        // Now remove the criterion from the cert decl and check that it is still used because it is used in the criteion level.
        $certdecl->delete();
        $this->assertTrue(utils::is_criterion_used($criterion));
        $observationcriterionlevel->delete();
        $observationcriterioncomment->delete();
        $this->assertFalse(utils::is_criterion_used($criterion));
    }
}
