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

namespace local\importer;

use advanced_testcase;
use cm_info;
use context_course;
use mod_competvet\local\importer\role_importer;
use stdClass;

/**
 * Role importer test class
 *
 * @package     mod_competvet
 * @copyright   2023 CALL Learning <contact@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \mod_competvet\local\importer\role_importer
 */
final class role_importer_test extends advanced_testcase {
    /**
     * Sample file path
     */
    const SAMPLE_FILE_PATH = '/mod/competvet/tests/fixtures/importer/role_assignments.csv';

    /**
     * @var $users array
     */
    protected $users = [];

    /**
     * @var $course stdClass|null
     */
    protected ?stdClass $course = null;

    /**
     * @var cm_info|null
     */
    protected ?cm_info $cminfo = null;

    /**
     * As we have a test that does write into the DB, we need to setup and tear down each time
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();

    }

    /**
     * Test import criterion.
     *
     * @return void
     * @covers ::import
     * @covers \mod_competvet\local\importer\role_importer::import
     * @dataProvider import_data
     */
    public function test_simple_import(string $filepath, array $userdata, array $expectedroles): void {
        global $CFG, $DB;
        $this->setup_scenario($userdata);
        $this->setAdminUser();
        $roleimporter = new role_importer($this->course->id, $this->cminfo->id);
        $roleimporter->import($CFG->dirroot . self::SAMPLE_FILE_PATH);
        foreach ($expectedroles as $rolename => $expectedusers) {
            $rid = $DB->get_field('role', 'id ', ['shortname' => $rolename]);
            $users = get_role_users($rid, \context_module::instance($this->cminfo->id), true);
            $users = array_values(array_map(fn($user) => $user->username, $users));
            sort($users);
            sort($expectedusers);
            $this->assertEquals($expectedusers, $users, 'Role ' . $rolename . ' does not match expected users.');
        }
    }


    /**
     * Data provider for import tests.
     *
     * @return array[]
     */
    public static function import_data(): array {
        return [
            'Import roles without existing roles' => [
                'filepath' => '/mod/competvet/tests/fixtures/importer/role_assignments.csv',
                'userdata' => [
                        's1' => 'student',
                        's2' => 'student',
                        't1' => 'student',
                        't2' => 'teacher',
                        'o1' => 'student',
                        'o2' => 'student',
                 ],
                'expectedroles' => [
                    'student' => ['s1', 's2', 't1', 'o1', 'o2'],
                    'teacher' => ['t2'],
                    'editingteacher' => [],
                    'observer' => ['s1', 's2', 'o1', 'o2', 't1'],
                    'evaluator' => ['s1', 't2'],
                ],
            ],
            'Import roles with existing roles' => [
                'filepath' => self::SAMPLE_FILE_PATH,
                'userdata' => [
                    's1' => 'student',
                    's2' => 'student',
                    't1' => 'teacher',
                    't2' => 'teacher',
                    'et1' => 'editingteacher',
                    'o1' => 'observer',
                    'o2' => 'observer',
                ],
                'expectedroles' => [
                    'student' => ['s1', 's2'],
                    'teacher' => ['t1', 't2'],
                    'editingteacher' => ['et1'],
                    'observer' => ['s1', 's2', 'o1', 'o2', 't1'],
                    'evaluator' => ['s1', 't2'],
                ],
            ],
        ];
    }

    /**
     * Setup the scenario for the test.
     *
     * @param array $userdata User data with username as key and role as value.
     * @return void
     */
    private function setup_scenario(array $userdata): void {
        $generator = $this->getDataGenerator();
        $this->users = [];

        $this->course = $generator->create_course(['fullname' => 'Test Course', 'shortname' => 'TC']);
        $instance = $generator->create_module('competvet', ['course' => $this->course->id, 'name' => 'Test Competvet']);
        $modinfo = \course_modinfo::instance($this->course->id);
        $this->cminfo = $modinfo->get_cm($instance->cmid);

        $roles = get_all_roles(context_course::instance($this->course->id));
        $roles = array_column($roles, 'id', 'shortname');
        foreach ($userdata as $username => $role) {
            $user = $generator->create_user(['username' => $username]);
            $generator->enrol_user($user->id, $this->getDataGenerator()->create_course()->id, $role);
            if (isset($roles[$role])) {
                role_assign($roles[$role], $user->id, \context_module::instance($this->cminfo->id)->id);
            }
            $this->users[$username] = $user;
        }
    }
}
