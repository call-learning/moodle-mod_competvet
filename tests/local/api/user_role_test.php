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
use cache;
use context_course;
use context_module;
use context_system;
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

    /**
     * A hidden situation must not count towards the aggregated user role for a user who cannot
     * view hidden activities, but it must count for a user who can (uservisible).
     *
     * The user is a student on the hidden situation A and an observer on the visible situation B.
     * For the normal user the hidden (student) situation is ignored, so the aggregated role is
     * "observer" and no conflict is raised. For the user who can view hidden activities both
     * situations are seen, the student and observer roles conflict, and the role is therefore
     * unknown (surfaced by the app as such).
     *
     * @return void
     */
    public function test_get_top_for_all_situations_excludes_hidden_situations(): void {
        $generator = $this->getDataGenerator();
        $coursea = $generator->create_course(['fullname' => 'Hidden Role Course A', 'shortname' => 'HRA']);
        $courseb = $generator->create_course(['fullname' => 'Hidden Role Course B', 'shortname' => 'HRB']);
        $modulea = $generator->create_module('competvet', ['course' => $coursea->id, 'name' => 'Sit A']);
        $moduleb = $generator->create_module('competvet', ['course' => $courseb->id, 'name' => 'Sit B']);
        $sita = situation::get_record(['competvetid' => $modulea->id], MUST_EXIST);
        $sitb = situation::get_record(['competvetid' => $moduleb->id], MUST_EXIST);

        // Hide the situation where the users are students.
        set_coursemodule_visible($modulea->cmid, 0);

        // A normal user: student on the hidden situation A, observer on the visible situation B.
        $normaluser = $generator->create_user(['username' => 'hidrolenormal']);
        $generator->enrol_user($normaluser->id, $coursea->id, 'student');
        $generator->enrol_user($normaluser->id, $courseb->id, 'observer');

        // The student role exists on the hidden situation (visibility is independent of the role).
        $this->assertSame('student', user_role::get_top($normaluser->id, $sita->get('id')));
        // The hidden situation is excluded from the list; the visible one is kept.
        cache::make('mod_competvet', 'usersituations')->delete($normaluser->id);
        $this->assertNotContains($sita->get('id'), situation::get_all_situations_id_for($normaluser->id));
        $this->assertContains($sitb->get('id'), situation::get_all_situations_id_for($normaluser->id));
        // So the aggregated role is observer: the hidden student is ignored and no conflict is raised.
        $this->assertSame('observer', user_role::get_top_for_all_situations($normaluser->id));

        // A privileged user (same enrolments, plus viewhiddenactivities) sees both situations.
        $privilegeduser = $generator->create_user(['username' => 'hidrolepriv']);
        $generator->enrol_user($privilegeduser->id, $coursea->id, 'student');
        $generator->enrol_user($privilegeduser->id, $courseb->id, 'observer');
        $systemcontext = context_system::instance();
        $roleid = create_role('Dummy view hidden role', 'dummyviewhiddenrole', 'Allows viewing hidden activities');
        assign_capability('moodle/course:viewhiddenactivities', CAP_ALLOW, $roleid, $systemcontext->id);
        role_assign($roleid, $privilegeduser->id, $systemcontext->id);
        accesslib_clear_all_caches_for_unit_testing();
        cache::make('mod_competvet', 'usersituations')->delete($privilegeduser->id);

        // Both situations are visible, so the student and observer roles conflict.
        try {
            user_role::get_top_for_all_situations($privilegeduser->id);
            $this->fail('Expected a conflictroles moodle_exception to be thrown.');
        } catch (\moodle_exception $e) {
            $this->assertSame('conflictroles', $e->errorcode);
        }
    }
}
