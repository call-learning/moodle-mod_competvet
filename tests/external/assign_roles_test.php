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

namespace mod_competvet\external;

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
final class assign_roles_test extends \advanced_testcase {
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
     * Test with non existing user.
     *
     * @covers \mod_competvet\external\assign_roles::execute
     * @runInSeparateProcess
     */
    public function test_assign_roles_user_not_exist(): void {
        $this->setAdminUser();
        $result = $this->assign_roles(
            [
                'userids' => [9999],
                'cmid' => $this->cminfo->id,
                'action' => 'add',
                'roleid' => 5,
            ]
        );
        $this->assertNotEmpty($result);
        $this->assertEquals([['userid' => 9999, 'action' => 'invaliduser']], $result['results']);
    }

    /**
     * Helper
     *
     * @param array $args
     * @return mixed
     */
    protected function assign_roles(array $args) {
        $validate = [\mod_competvet\external\assign_roles::class, 'validate_parameters'];
        $params = call_user_func(
            $validate,
            \mod_competvet\external\assign_roles::execute_parameters(),
            $args
        );
        $params = array_values($params);
        $returnvalue = \mod_competvet\external\assign_roles::execute(...$params);
        return external_api::clean_returnvalue(\mod_competvet\external\assign_roles::execute_returns(), $returnvalue);
    }

    /**
     * Test with non existing module
     *
     * @covers \mod_competvet\external\assign_roles::execute
     * @runInSeparateProcess
     */
    public function test_assign_roles_cm_not_exist(): void {
        $this->setAdminUser();
        $this->expectException(dml_missing_record_exception::class);
        $this->assign_roles(
            [
                'userids' => [$this->users['s1']->id],
                'cmid' => 999,
                'action' => 'add',
                'roleid' => 5,
            ]
        );
    }

    /**
     * Test with non existing role
     *
     * @covers \mod_competvet\external\assign_roles::execute
     * @runInSeparateProcess
     */
    public function test_assign_roles_role_not_exist(): void {
        $this->setAdminUser();
        $this->expectException(\moodle_exception::class);
        $this->assign_roles(
            [
                'userids' => [$this->users['s1']->id],
                'cmid' => $this->cminfo->id,
                'action' => 'add',
                'roleid' => 9999,
            ]
        );
    }

    /**
     * Call assign_role with valid parameters and check the result.
     *
     * @param array $users
     * @param string $action
     * @param string $role
     * @param string $currentuser
     * @param array $expected
     *
     * @covers       \mod_competvet\external\assign_roles::execute
     * @dataProvider assign_role_data
     * @runInSeparateProcess
     */
    public function test_user_profile_existing_test(
        array $users,
        string $action,
        string $role,
        string $currentuser,
        array $expected
    ): void {
        global $DB;
        if ($currentuser === 'admin') {
            $this->setAdminUser();
        } else {
            $this->setUser($this->users[$currentuser]);
        }
        $userids = array_map(fn($user) => $this->users[$user]->id, $users);
        $roleid = $DB->get_field('role', 'id ', ['shortname' => $role]);
        if (isset($expected['exception'])) {
            $this->expectException($expected['exception']);
        }
        $actual = $this->assign_roles(
            [
                'userids' => $userids,
                'cmid' => $this->cminfo->id,
                'action' => $action,
                'roleid' => $roleid,
            ]
        );
        $expectedresults = array_map(
            fn($data) => ['userid' => $this->users[$data['userid']]->id, 'action' => $data['action']],
            $expected['results'],
        );
        $this->assertEquals($expectedresults, $actual['results']);
        foreach ($expected['rolechecks'] ?? [] as $rolename => $expectedusers) {
            $rid = $DB->get_field('role', 'id ', ['shortname' => $rolename]);
            $users = get_role_users($rid, \context_module::instance($this->cminfo->id), true);
            $users = array_values(array_map(fn($user) => $user->username, $users));
            sort($users);
            sort($expectedusers);
            $this->assertEquals($expectedusers, $users);
        }
    }

    /**
     * Data provider for assign_role_data
     *
     * @return array
     */
    public static function assign_role_data(): array {
        return [
            'Add student as admin' => [
                'users' => ['s1', 's2'],
                'action' => 'add',
                'role' => 'editingteacher',
                'currentuser' => 'admin',
                'expected' => [
                    'results' => [
                        ['userid' => 's1', 'action' => 'added'],
                        ['userid' => 's2', 'action' => 'added'],
                    ],
                    'rolechecks' => [
                        'editingteacher' => ['et1', 's1', 's2'],
                    ],
                ],
            ],
            'Remove student as admin' => [
                'users' => ['s1', 's2'],
                'action' => 'remove',
                'role' => 'editingteacher',
                'currentuser' => 'admin',
                'expected' => [
                    'results' => [
                        ['userid' => 's1', 'action' => 'removed'],
                        ['userid' => 's2', 'action' => 'removed'],
                    ],
                    'rolechecks' => [
                        'editingteacher' => ['et1'],
                    ],
                ],
            ],
            'Remove student as student' => [
                'users' => ['s1', 's2'],
                'action' => 'remove',
                'role' => 'editingteacher',
                'currentuser' => 's1',
                'expected' => [
                    'exception' => \core\exception\required_capability_exception::class,
                ],
            ],
            'Add student as observer as editingteacher' => [
                'users' => ['s1', 's2'],
                'action' => 'add',
                'role' => 'observer',
                'currentuser' => 'admin',
                'expected' => [
                    'results' => [
                        ['userid' => 's1', 'action' => 'added'],
                        ['userid' => 's2', 'action' => 'added'],
                    ],
                    'rolechecks' => [
                        'observer' => ['o1', 'o2', 's1', 's2'],
                    ],
                ],
            ],
        ];
    }
}
