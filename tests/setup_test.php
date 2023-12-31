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
use context_course;
use context_module;
use context_system;
use mod_competvet\local\persistent\criterion;
use mod_competvet\local\persistent\evaluation_grid;
use mod_competvet\task\post_install;

/**
 * Setup Tests
 *
 * @package     mod_competvet
 * @copyright   2023 CALL Learning <contact@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class setup_test extends advanced_testcase {
    /**
     * Test roles creation at install.
     *
     * @return void
     *
     * @covers \mod_competvet\setup::create_update_roles
     */
    public function test_roles_setup() {
        $existingroles = get_all_roles();
        $existingrolesshortnames = array_map(function ($role) {
            return $role->shortname;
        }, $existingroles); // Shortname to ID.
        foreach (competvet::COMPETVET_ROLES as $rolename => $roledef) {
            $this->assertContains($rolename, $existingrolesshortnames);
        }
    }

    /**
     * Test tags created at install
     *
     * @return void
     *
     * @covers \mod_competvet\setup::create_update_roles
     */
    public function test_tags_setup() {
        global $DB;
        $situationscollectionid = \core_tag_area::get_collection('mod_competvet', 'competvet_situation');
        $collection = \core_tag_collection::get_by_id($situationscollectionid);
        $situationtagsname =
            $DB->get_fieldset_select('tag', 'name', 'tagcollid = :collectionid', ['collectionid' => $collection->id]);

        foreach (setup::SITUATION_TAG_LS as $tagsn) {
            $this->assertContains($tagsn, $situationtagsname);
        }
    }


    /**
     * Test default grid created and installed
     *
     * @return void
     *
     * @covers \mod_competvet\setup::crerate_default_grid
     */
    public function test_default_grid_setup() {
        $evalgrid = evaluation_grid::get_default_grid();
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
     * Test roles access.
     *
     * @return void
     *
     * @covers \mod_competvet\setup::create_update_roles
     */
    public function test_roles_access() {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $coursemodule = $generator->create_module('competvet', ['course' => $course->id]);
        $this->assert_context_capabilities(competvet::COMPETVET_ROLES, $course, $coursemodule);
    }

    /**
     * Test roles access if we change definition.
     *
     * @return void
     *
     * @covers \mod_competvet\setup::create_update_roles
     */
    public function test_roles_access_with_update() {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $coursemodule = $generator->create_module('competvet', ['course' => $course->id]);
        // Now update roles.
        $newroledefs = competvet::COMPETVET_ROLES;
        $newroledefs['responsibleucue']['permissions'][CONTEXT_COURSE]['mod/competvet:canaskobservation'] = CAP_ALLOW;
        $newroledefs['admincompetvet']['permissions'][CONTEXT_SYSTEM]['mod/competvet:candoeverything'] = CAP_PREVENT;
        setup::create_update_roles($newroledefs);

        $this->assert_context_capabilities($newroledefs, $course, $coursemodule);
    }

    /**
     * Utility function to assert that we have the right capabilities setup.
     *
     * @param array $roledefs
     * @param \stdClass $course
     * @param \stdClass $coursemodule
     * @return void
     */
    private function assert_context_capabilities(array $roledefs, \stdClass $course, \stdClass $coursemodule) {
        $generator = $this->getDataGenerator();
        foreach ($roledefs as $rolename => $roledef) {
            $user = $generator->create_and_enrol($course, $rolename);
            foreach ($roledef['permissions'] as $contextlevel => $permissions) {
                if ($contextlevel == CONTEXT_SYSTEM) {
                    global $DB;
                    $roleid = $DB->get_field('role', 'id', ['shortname' => $rolename]);
                    role_assign($roleid, $user->id, context_system::instance()->id);
                }
                foreach ($permissions as $permissionname => $permissionvalue) {
                    switch ($contextlevel) {
                        case CONTEXT_COURSE:
                            $context = context_course::instance($course->id);
                            break;
                        case CONTEXT_MODULE:
                            $context = context_module::instance($coursemodule->id);
                            break;
                        case CONTEXT_SYSTEM:
                        default:
                            $context = context_system::instance();
                    }
                    $this->assertEquals(
                        $permissionvalue === CAP_ALLOW,
                        has_capability($permissionname, $context, $user->id),
                        "Failed for capability $permissionname in context $contextlevel for role $rolename"
                    );
                }
            }
        }
    }
}
