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
use core_user;
use DateTime;
use mod_competvet\local\persistent\observation;
use mod_competvet\local\persistent\planning;
use mod_competvet\local\persistent\situation;
use mod_competvet\tests\test_data_definition;
use mod_competvet\tests\test_helpers;
use stdClass;

/**
 * Planning API test
 *
 * @package     mod_competvet
 * @copyright   2023 CALL Learning <contact@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class plannings_test extends advanced_testcase {
    use test_data_definition;

    /**
     * @var stdClass $courses
     */
    protected $courses;

    /**
     * All for user provider with planning
     *
     * @return array[]
     */
    public static function all_situations_with_planning(): array {
        global $CFG;
        $results = [];
        $startdate = self::get_start_date()->getTimestamp();
        include_once($CFG->dirroot . '/mod/competvet/tests/fixtures/plannings_tests_results.php');
        return [
            'student1 situations with no future' => [
                'student1',
                true, // No future.
                $results['student1results'],
            ],
            'student1 situations with future' => [
                'student1',
                false,
                $results['student1resultswithfuture'],
            ],
            'observer1 situations' => [
                'observer1',
                true, // No future.
                $results['observer1results'],
            ],
            'observer1 situations with future' => [
                'observer1',
                false,
                $results['observer1resultswithfuture'],
            ],
        ];
    }

    /**
     * Setup the test
     *
     * @return void
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->prepare_scenario('set_1');
        $this->set_current_date();
        $this->setAdminUser(); // Needed for report builder to work.
    }

    /**
     * Get all with planning for user
     *
     * @param string $username
     * @param bool $nofuture
     * @param array $expected
     * @return void
     * @dataProvider all_situations_with_planning
     * @covers       \mod_competvet\local\api\situations::get_all_situations_for
     */
    public function test_get_plannings_for_situation_id(string $username, bool $nofuture, array $expected): void {
        $user = core_user::get_user_by_username($username);
        $situations = situation::get_all_situations_id_for($user->id);
        $allplannings = [];
        foreach ($situations as $situationid) {
            $situation = situation::get_record(['id' => $situationid]);
            $shortname = $situation->get('shortname');
            $plannings = plannings::get_plannings_for_situation_id($situationid, $user->id, $nofuture);
            test_helpers::remove_elements_for_assertions($plannings, ['id']);
            $allplannings[$shortname] =
                array_merge($allplannings[$shortname] ?? [], $plannings);
        }
        ksort($allplannings);
        ksort($expected);
        $this->assertSame($expected, $allplannings);
    }

    /**
     * Test that situation_has_user_data reflects whether any planning of the situation contains user data.
     *
     * @return void
     * @covers       \mod_competvet\local\api\plannings::situation_has_user_data
     */
    public function test_situation_has_user_data(): void {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $competvet = $generator->get_plugin_generator('mod_competvet')->create_instance(['course' => $course->id]);
        $situation = situation::get_record(['competvetid' => $competvet->id]);

        // A fresh situation has no user data yet.
        $this->assertFalse(plannings::situation_has_user_data($situation->get('id')));

        // A planning with an observation makes it have user data.
        $planning = new planning(0, (object) [
            'situationid' => $situation->get('id'),
            'groupid' => 0,
            'startdate' => time(),
            'enddate' => time() + 86400,
            'session' => '2026',
        ]);
        $planning->create();
        $observation = new observation(0, (object) [
            'situationid' => 0,
            'observerid' => 0,
            'observedid' => 0,
            'planningid' => $planning->get('id'),
            'studentid' => 0,
            'timeobserved' => time(),
        ]);
        $observation->create();

        $this->assertTrue(plannings::situation_has_user_data($situation->get('id')));
    }
}
