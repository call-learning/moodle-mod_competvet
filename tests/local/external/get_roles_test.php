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

namespace local\external;

use cm_info;
use context_course;
use dml_missing_record_exception;
use external_api;
use stdClass;

/**
 * Assign Role tests
 *
 * @package     mod_competvet
 * @copyright   2023 CALL Learning <contact@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class get_roles_test extends \advanced_testcase {
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
        $generator = $this->getDataGenerator();
        $this->users = [];
        $data = [
            's1' => 'student',
            's2' => 'student',
            't1' => 'teacher',
            'et1' => 'editingteacher',
            'o1' => 'observer',
            'o2' => 'observer',
            'user@vet-alfor.fr' => 'student', // Check we can handle email addresses as usernames.
        ];
        $this->course = $generator->create_course(['fullname' => 'Test Course', 'shortname' => 'TC']);
        $instance = $generator->create_module('competvet', ['course' => $this->course->id, 'name' => 'Test Competvet']);
        $modinfo = \course_modinfo::instance($this->course->id);
        $this->cminfo = $modinfo->get_cm($instance->cmid);

        $roles = get_all_roles(context_course::instance($this->course->id));
        $roles = array_column($roles, 'id', 'shortname');
        foreach ($data as $username => $role) {
            $user = $generator->create_user(['username' => $username]);
            $roleid = $roles[$role] ?? null;
            if ($roleid) {
                $generator->enrol_user($user->id, $this->getDataGenerator()->create_course()->id, $role);
                role_assign($roleid, $user->id, \context_module::instance($this->cminfo->id)->id);
            }
            $this->users[$username] = $user;
        }
    }

    /**
     * Test with non existing module
     *
     * @covers \mod_competvet\external\get_roles::execute
     * @runInSeparateProcess
     */
    public function test_assign_roles_cm_not_exist(): void {
        $this->setAdminUser();
        $this->expectException(dml_missing_record_exception::class);
        $this->get_roles(
            [
                'cmid' => 999,
            ]
        );
    }

    /**
     * Helper
     *
     * @param array $args
     * @return mixed
     */
    protected function get_roles(array $args) {
        $validate = [\mod_competvet\external\get_roles::class, 'validate_parameters'];
        $params = call_user_func(
            $validate,
            \mod_competvet\external\get_roles::execute_parameters(),
            $args
        );
        $params = array_values($params);
        $returnvalue = \mod_competvet\external\get_roles::execute(...$params);
        return external_api::clean_returnvalue(\mod_competvet\external\get_roles::execute_returns(), $returnvalue);
    }

    /**
     * Call assign_role with valid parameters and check the result.
     *
     * @covers       \mod_competvet\external\assign_role::execute
     * @dataProvider get_role_data
     * @runInSeparateProcess
     */
    public function test_get_role_basic(
        string $currentuser,
        array $expected
    ): void {
        global $DB;
        if ($currentuser === 'admin') {
            $this->setAdminUser();
        } else {
            $this->setUser($this->users[$currentuser]);
        }
        if (isset($expected['exception'])) {
            $this->expectException($expected['exception']);
        }
        $actual = $this->get_roles(
            [
                'cmid' => $this->cminfo->id,
            ]
        );
        // Now compare the results.
        foreach ($expected['results'] as $role => $userlist) {
            $actualusers = array_merge(
                ...array_map(
                    fn($data) => array_column($data['users'], 'username'),
                    array_filter($actual, fn($item) => $item['roleshortname'] === $role)
                )
            );
            sort($actualusers);
            sort($userlist);
            $this->assertEquals(
                $userlist,
                $actualusers,
                "Expected users for role '$role' are different."
            );
        }
    }
    /**
     * Data provider for get_role_data
     *
     * @return array
     */
    public static function get_role_data(): array {
        return [
            'Get roles as admin' => [
                'currentuser' => 'admin',
                'expected' => [
                    'results' => [
                        'teacher' => ['t1'],
                        'editingteacher' => ['et1'],
                        'observer' => ['o1', 'o2'],
                        'student' => ['s1', 's2', 'user@vet-alfor.fr'],
                    ],
                ],
            ],
            'Get roles as student' => [
                'currentuser' => 's1',
                'expected' => [
                    'results' => [

                    ],
                    'exception' => \moodle_exception::class,
                ],
            ],
        ];
    }

}
