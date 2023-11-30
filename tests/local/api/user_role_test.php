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
namespace mod_competvet\local\api;

use advanced_testcase;
use context_module;
use mod_competvet\competvet;
use mod_competvet\local\persistent\situation;
use mod_competvet\task\post_install;

/**
 * User role test
 *
 * @package     mod_competvet
 * @copyright   2023 CALL Learning <contact@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_role_test extends advanced_testcase {
    /**
     * @var \stdClass $course
     */
    protected $course;
    /**
     * @var \stdClass $coursemodule
     */
    protected $coursemodule;

    /**
     * User enrolments provider
     *
     * @return array[]
     */
    public static function user_enrolments_provider_top(): array {
        return array_map(function ($item) {
            return ['roles' => $item['roles'], 'expected' => $item['expected_top']];
        }, self::basic_provider());
    }

    /**
     * User enrolments provider for both get_top and get_all
     *
     * @return array[]
     */
    private static function basic_provider(): array {
        return [
            'simple student' => [
                'roles' => ['student'],
                'expected_top' => 'student',
                'expected_all' => ['student'],
            ],
            'observer and assessor' => [
                'roles' => ['assessor', 'observer', 'editingteacher'],
                'expected_top' => 'assessor',
                'expected_all' => ['assessor', 'observer'],
            ],
            'assessor and assessor' => [
                'roles' => ['assessor', 'evaluator', 'teacher'],
                'expected_top' => 'evaluator',
                'expected_all' => ['assessor', 'evaluator'],
            ],
            'manager so unknown' => [
                'roles' => ['manager'],
                'expected_top' => 'unknown',
                'expected_all' => ['unknown'],
            ],
            'observer and student' => [
                'roles' => ['observer', 'student'],
                'expected_top' => 'exception',
                'expected_all' => ['student', 'observer'],
            ],
        ];
    }

    /**
     * User enrolments provider
     *
     * @return array[]
     */
    public static function user_enrolments_provider_all(): array {
        return array_map(function ($item) {
            return ['roles' => $item['roles'], 'expected' => $item['expected_all']];
        }, self::basic_provider());
    }

    /**
     * Setup
     *
     * @return void
     */
    public function setUp(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $this->course = $generator->create_course();
        $this->coursemodule = $generator->create_module('competvet', ['course' => $this->course->id, 'shortname' => 'SIT1']);
    }

    /**
     * Test get_top_for_all_situations
     *
     * @param string $courserole
     * @param array $situationsroles
     * @param string $expected
     * @return void
     * @covers       \mod_competvet\local\api\user_role::get_top
     * @dataProvider all_situations_provider
     */
    public function test_get_top_for_all_situations(string $courserole, array $situationsroles, string $expected) {
        $generator = $this->getDataGenerator();
        $generator->create_module('competvet', ['course' => $this->course->id, 'shortname' => 'SIT2']);
        $generator->create_module('competvet', ['course' => $this->course->id, 'shortname' => 'SIT3']);

        $user = $generator->create_and_enrol($this->course, $courserole);
        foreach ($situationsroles as $situationsn => $roles) {
            $situation = situation::get_record(['shortname' => $situationsn]);
            $competvet = competvet::get_from_instance_id($situation->get('competvetid'));
            foreach ($roles as $role) {
                global $DB;
                $roleid = $DB->get_field('role', 'id', ['shortname' => $role]);
                role_assign($roleid, $user->id, $competvet->get_context());
            }
        }
        if ($expected === 'exception') {
            $this->expectException(\moodle_exception::class);
            user_role::get_top_for_all_situations($user->id);
        } else {
            $this->assertEquals($expected, user_role::get_top_for_all_situations($user->id));
        }
    }

    /**
     * All situation providers
     * @return array[]
     */
    public static function all_situations_provider(): array {
        return [
            'simple student' => [
                'courserole' => 'student',
                'situationsroles' => [
                    'SIT1' => ['student'],
                    'SIT2' => ['student'],
                ],
                'expected' => 'student',
            ],
            'assessor, evaluator and observer' => [
                'courserole' => 'assessor',
                'situationsroles' => [
                    'SIT1' => ['assessor'],
                    'SIT2' => ['evaluator'],
                    'SIT3' => ['observer'],
                ],
                'expected' => 'evaluator',
            ],
            'assessor and evaluator' => [
                'courserole' => 'assessor',
                'situationsroles' => [
                    'SIT1' => ['assessor'],
                    'SIT2' => ['evaluator'],
                ],
                'expected' => 'evaluator',
            ],
            'conflicting roles' => [
                'courserole' => 'student',
                'situations' => [
                    'SIT1' => ['student'],
                    'SIT2' => ['observer'],
                ],
                'expected' => 'exception',
            ],
        ];
    }

    /**
     * Test get top user type
     *
     * @param $roles
     * @param $expected
     * @return void
     * @covers       \mod_competvet\local\api\user_role::get_top
     * @dataProvider user_enrolments_provider_top
     */
    public function test_get_top($roles, $expected) {
        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        foreach ($roles as $role) {
            $generator->enrol_user($user->id, $this->course->id, $role);
        }
        $competvet = competvet::get_from_instance_id($this->coursemodule->id);
        $situation = $competvet->get_situation();
        if ($expected === 'exception') {
            $this->expectException(\moodle_exception::class);
        }
        $this->assertEquals($expected, user_role::get_top($user->id, $situation->get('id')));
    }

    /**
     * Test get top user type
     *
     * @param $roles
     * @param $expected
     * @return void
     * @covers       \mod_competvet\local\api\user_role::get_all
     * @dataProvider user_enrolments_provider_all
     */
    public function test_get_all($roles, $expected) {
        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        foreach ($roles as $role) {
            $generator->enrol_user($user->id, $this->course->id, $role);
        }
        $competvet = competvet::get_from_instance_id($this->coursemodule->id);
        $situation = $competvet->get_situation();
        if ($expected === 'exception') {
            $this->expectException(\moodle_exception::class);
        }
        $allroles = user_role::get_all($user->id, $situation->get('id'));
        sort($allroles);
        sort($expected);
        $this->assertEquals($expected, $allroles);
    }
}
