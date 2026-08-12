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
use context_course;
use context_module;
use core_user;
use mod_competvet\local\persistent\situation;
use mod_competvet\tests\test_data_definition;

/**
 * User role test
 *
 * @package     mod_competvet
 * @copyright   2023 CALL Learning <contact@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\mod_competvet\local\api\user_role::class)]
final class user_role_test extends advanced_testcase {
    use test_data_definition;

    /**
     * User enrolments provider
     *
     * @return \Generator
     */
    public static function user_enrolments_provider_all(): \Generator {
        foreach (self::basic_provider() as $name => $item) {
            yield $name => ['user' => $item['user'], 'expected' => $item['expected_all']];
        }
    }

    /**
     * User enrolments provider for both get_top and get_all
     *
     * @return \Generator
     */
    private static function basic_provider(): \Generator {
        yield from [
            'simple student 1' => [
                'user' => 'student1',
                'expected_top' => ['SIT1' => 'student', 'SIT2' => 'student', 'SIT3' => 'student', 'SIT4' => 'student',
                    'SIT5' => 'student', 'SIT6' => 'student', 'SIT7' => 'student', 'SIT8' => 'student', 'SIT9' => 'student', ],
                'expected_all' => ['SIT1' => ['student'], 'SIT2' => ['student'], 'SIT3' => ['student'], 'SIT4' => ['student'],
                    'SIT5' => ['student'], 'SIT6' => ['student'], 'SIT7' => ['student'], 'SIT8' => ['student'],
                    'SIT9' => ['student'], ],
            ],
            'simple student 2' => [
                'user' => 'student2',
                'expected_top' => ['SIT1' => 'student', 'SIT2' => 'student', 'SIT3' => 'student', 'SIT4' => 'student',
                    'SIT5' => 'student', 'SIT6' => 'student', 'SIT7' => 'student', 'SIT8' => 'student', 'SIT9' => 'student', ],
                'expected_all' => ['SIT1' => ['student'], 'SIT2' => ['student'], 'SIT3' => ['student'], 'SIT4' => ['student'],
                    'SIT5' => ['student'], 'SIT6' => ['student'], 'SIT7' => ['student'], 'SIT8' => ['student'],
                    'SIT9' => ['student'], ],
            ],
            'observer and evaluator' => [
                'user' => 'observerandevaluator',
                'expected_top' => ['SIT1' => 'observer', 'SIT2' => 'observer', 'SIT3' => 'observer', 'SIT4' => 'unknown',
                    'SIT5' => 'unknown', 'SIT6' => 'unknown', 'SIT7' => 'evaluator', 'SIT8' => 'evaluator',
                    'SIT9' => 'evaluator', ],
                'expected_all' => ['SIT1' => ['observer'], 'SIT2' => ['observer'], 'SIT3' => ['observer'], 'SIT4' => ['unknown'],
                    'SIT5' => ['unknown'], 'SIT6' => ['unknown'], 'SIT7' => ['evaluator'], 'SIT8' => ['evaluator'],
                    'SIT9' => ['evaluator'], ],
            ],
            'manager so unknown' => [
                'user' => 'manager',
                'expected_top' => ['SIT1' => 'unknown', 'SIT2' => 'unknown', 'SIT3' => 'unknown', 'SIT4' => 'unknown',
                    'SIT5' => 'unknown', 'SIT6' => 'unknown', 'SIT7' => 'unknown', 'SIT8' => 'unknown', 'SIT9' => 'unknown', ],
                'expected_all' => ['SIT1' => ['unknown'], 'SIT2' => ['unknown'], 'SIT3' => ['unknown'], 'SIT4' => ['unknown'],
                    'SIT5' => ['unknown'], 'SIT6' => ['unknown'], 'SIT7' => ['unknown'], 'SIT8' => ['unknown'],
                    'SIT9' => ['unknown'], ],
            ],
            'observer and student' => [
                'user' => 'studentandobserver',
                'expected_top' => ['SIT1' => 'unknown', 'SIT2' => 'unknown', 'SIT3' => 'unknown', 'SIT4' => 'unknown',
                    'SIT5' => 'unknown', 'SIT6' => 'unknown', 'SIT7' => 'exception', 'SIT8' => 'exception',
                    'SIT9' => 'exception', ],
                'expected_all' => ['SIT1' => ['unknown'], 'SIT2' => ['unknown'], 'SIT3' => ['unknown'], 'SIT4' => ['unknown'],
                    'SIT5' => ['unknown'], 'SIT6' => ['unknown'], 'SIT7' => ['student', 'observer'],
                    'SIT8' => ['student', 'observer'],
                    'SIT9' => ['student', 'observer'], ],
            ],
            'observer and teacher' => [
                'user' => 'observerandteacher',
                'expected_top' => ['SIT1' => 'unknown', 'SIT2' => 'unknown', 'SIT3' => 'unknown', 'SIT4' => 'unknown',
                    'SIT5' => 'unknown', 'SIT6' => 'unknown', 'SIT7' => 'observer', 'SIT8' => 'observer', 'SIT9' => 'observer', ],
                'expected_all' => ['SIT1' => ['unknown'], 'SIT2' => ['unknown'], 'SIT3' => ['unknown'], 'SIT4' => ['unknown'],
                    'SIT5' => ['unknown'], 'SIT6' => ['unknown'], 'SIT7' => ['observer'],
                    'SIT8' => ['observer'],
                    'SIT9' => ['observer'], ],
            ],
        ];
    }

    /**
     * User enrolments provider
     *
     * @return \Generator
     */
    public static function user_enrolments_provider_top(): \Generator {
        foreach (self::basic_provider() as $name => $item) {
            yield $name => ['user' => $item['user'], 'expected' => $item['expected_top']];
        }
    }

    /**
     * All situation providers
     *
     * @return \Generator
     */
    public static function all_situations_provider(): \Generator {
        yield from [
            'simple student1' => [
                'user' => 'student1',
                'expected' => 'student',
            ],
            'simple student2' => [
                'user' => 'student1',
                'expected' => 'student',
            ],
            'evaluator and observer' => [
                'user' => 'observerandevaluator',
                'expected' => 'evaluator',
            ],
            'conflicting roles' => [
                'user' => 'studentandobserver',
                'expected' => 'exception',
            ],
            'observer and teacher' => [
                'user' => 'observerandteacher',
                'expected' => 'observer',
            ],
        ];
    }

    /**
     * Setup
     *
     * @return void
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->prepare_scenario('set_1');
        $this->set_current_date();
    }

    /**
     * Test get_top_for_all_situations
     *
     * @param string $user
     * @param string $expected
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('all_situations_provider')]
    public function test_get_top_for_all_situations(string $user, string $expected): void {
        $user = core_user::get_user_by_username($user);
        if ($expected === 'exception') {
            $this->expectException(\moodle_exception::class);
            user_role::get_top_for_all_situations($user->id);
        } else {
            $this->assertEquals($expected, user_role::get_top_for_all_situations($user->id));
        }
    }

    /**
     * Test get top user type
     *
     * @param string $user
     * @param array $expected
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('user_enrolments_provider_top')]
    public function test_get_top(string $user, array $expected): void {
        $user = core_user::get_user_by_username($user);
        $situations = situation::get_records([], 'shortname', 'ASC');
        $result = [];
        foreach ($situations as $situation) {
            try {
                $result[$situation->get('shortname')] = user_role::get_top($user->id, $situation->get('id'));
            } catch (\moodle_exception $e) {
                $result[$situation->get('shortname')] = 'exception';
            }
        }
        $this->assertSame($expected, $result);
    }

    /**
     * Test get top user type
     *
     * @param string $user
     * @param array $expected
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('user_enrolments_provider_all')]
    public function test_get_all(string $user, array $expected): void {
        $user = core_user::get_user_by_username($user);
        $situations = situation::get_records([], 'shortname', 'ASC');
        $result = [];
        foreach ($situations as $situation) {
            try {
                $result[$situation->get('shortname')] = user_role::get_all($user->id, $situation->get('id'));
            } catch (\moodle_exception $e) {
                $result[$situation->get('shortname')] = 'exception';
            }
        }
        $this->assertSame($expected, $result);
    }

    /**
     * Test that inherited course roles do not affect activity role classification.
     *
     * @return void
     */
    public function test_get_top_ignores_parent_context_roles(): void {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['fullname' => 'Role Scope Course', 'shortname' => 'RSC']);
        $instance = $generator->create_module('competvet', ['course' => $course->id, 'name' => 'Scoped Competvet']);
        $modinfo = \course_modinfo::instance($course->id);
        $cm = $modinfo->get_cm($instance->cmid);
        $modulecontext = context_module::instance($cm->id);

        $user = $generator->create_user(['username' => 'scopedobserver']);
        $roles = array_column(get_all_roles(context_course::instance($course->id)), 'id', 'shortname');
        role_assign($roles['student'], $user->id, context_course::instance($course->id)->id);
        role_assign($roles['observer'], $user->id, $modulecontext->id);

        $situation = situation::get_record(['competvetid' => $instance->id], MUST_EXIST);

        $this->assertSame(['observer'], user_role::get_all($user->id, $situation->get('id')));
        $this->assertSame('observer', user_role::get_top($user->id, $situation->get('id')));
    }

    /**
     * Teacher roles inherited from the course do not override a direct observer role.
     *
     * @return void
     */
    public function test_get_top_ignores_inherited_teacher_for_direct_observer(): void {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['fullname' => 'Teacher Scope Course', 'shortname' => 'TSC']);
        $instance = $generator->create_module('competvet', ['course' => $course->id, 'name' => 'Teacher Scoped Competvet']);
        $modulecontext = context_module::instance($instance->cmid);
        $coursecontext = context_course::instance($course->id);
        $user = $generator->create_user(['username' => 'directobserver']);
        $roles = array_column(get_all_roles($coursecontext), 'id', 'shortname');

        role_assign($roles['teacher'], $user->id, $coursecontext->id);
        role_assign($roles['observer'], $user->id, $modulecontext->id);

        $situation = situation::get_record(['competvetid' => $instance->id], MUST_EXIST);
        $this->assertSame(['observer'], user_role::get_all($user->id, $situation->get('id')));
        $this->assertSame('observer', user_role::get_top($user->id, $situation->get('id')));
    }
}
