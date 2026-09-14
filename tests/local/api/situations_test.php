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
use core_user;
use DateTime;
use mod_competvet\competvet;
use mod_competvet\local\persistent\situation;
use mod_competvet\tests\test_data_definition;
use mod_competvet\tests\test_helpers;
use stdClass;

/**
 * Situations API test
 *
 * @package     mod_competvet
 * @copyright   2023 CALL Learning <contact@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\mod_competvet\local\api\situations::class)]
final class situations_test extends advanced_testcase {
    use test_data_definition;

    /**
     * @var stdClass $courses
     */
    protected $courses;

    /**
     * All for user provider with planning
     *
     * @return \Generator
     */
    public static function all_for_user_provider_with_planning(): \Generator {
        global $CFG;
        $results = [];
        $startdate = self::get_start_date()->getTimestamp();
        include_once($CFG->dirroot . '/mod/competvet/tests/fixtures/situation_tests_results.php');
        yield from [
            'student1 situations' => [
                'student1',
                $results['student1results'],
            ],
            'student2 situations' => [
                'student2',
                $results['student2results'],
            ],
            'observer1 situations' => [
                'observer1',
                $results['observer1results'],
            ],
            'observer2 situations' => [
                'observer2',
                $results['observer2results'],
            ],
            'teacher1 situations' => [
                'teacher1',
                $results['teacher1results'],
            ],
            'observer and teacher'  => [
                'observerandteacher',
                $results['observerandteacherresults'],
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
     * @param array $expected
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('all_for_user_provider_with_planning')]
    public function test_get_all_situations_with_planning_for(string $username, array $expected): void {
        $user = core_user::get_user_by_username($username);
        $situations = situations::get_all_situations_with_planning_for($user->id);
        usort($situations, function ($sit1, $sit2) {
            return $sit1['shortname'] <=> $sit2['shortname'];
        });
        usort($expected, function ($sit1, $sit2) {
            return $sit1['shortname'] <=> $sit2['shortname'];
        });
        test_helpers::remove_elements_for_assertions($situations, ['id', 'intro', 'roles']);
        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($situations));
    }

    /**
     * Get all criteria test
     *
     * @return void
     */
    public function test_get_all_criteria(): void {
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $criteria = situations::get_all_criteria($situation->get('id'));
        $this->assertCount(40, $criteria);
        $this->assertEquals([
            'id' => 1,
            'label' => 'Savoir être',
            'idnumber' => 'Q001',
            'sort' => 0,
            'parentid' => 0,
            'parentlabel' => null,
            'parentidnumber' => null,
            'grade' => null,
        ], $criteria[0]);
        $this->assertEquals([
            'id' => 3,
            'label' => 'Respect des interlocuteurs (clients, personnels, encadrants, pairs, ...)',
            'idnumber' => 'Q003',
            'sort' => 2,
            'parentid' => 1,
            'parentlabel' => 'Savoir être',
            'parentidnumber' => 'Q001',
            'grade' => null,
        ], $criteria[8]);
    }

    /**
     * The app read path must not expose a hidden situation, even for users able to view hidden
     * activities, while the web (capability-based) read path still does.
     *
     * @return void
     */
    public function test_get_all_situations_with_planning_for_excludes_hidden(): void {
        global $DB;
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $competvet = competvet::get_from_situation_id($situation->get('id'));
        $observer = core_user::get_user_by_username('observer1');
        $courseid = $competvet->get_course_module()->course;

        // Clear any cached situation list so the assertions are computed from scratch.
        cache::make('mod_competvet', 'usersituations')->delete($observer->id);

        // While visible, the app path includes the situation.
        $situations = situations::get_all_situations_with_planning_for($observer->id);
        $this->assertContains('SIT1', array_column($situations, 'shortname'));

        // Grant the capability to view hidden activities (to the observer role) and hide the activity.
        $context = context_course::instance($courseid);
        $observerrole = $DB->get_record('role', ['shortname' => 'observer'], '*', MUST_EXIST);
        assign_capability('moodle/course:viewhiddenactivities', CAP_ALLOW, $observerrole->id, $context->id);
        accesslib_clear_all_caches_for_unit_testing();
        set_coursemodule_visible($competvet->get_course_module_id(), 0);

        // The web (capability-based) read path still includes the hidden situation.
        $websituations = situation::get_all_situations_in_course_id_for($observer->id, $courseid);
        $this->assertContains($situation->get('id'), $websituations);

        // The app (strict) read path excludes the hidden situation.
        $appsituations = situations::get_all_situations_with_planning_for($observer->id);
        $this->assertNotContains('SIT1', array_column($appsituations, 'shortname'));
    }
}
