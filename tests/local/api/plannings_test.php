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
namespace local\api;
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/mod/competvet/tests/test_data_definition.php');

use advanced_testcase;
use core_user;
use mod_competvet\local\api\plannings;
use mod_competvet\local\api\situations;
use mod_competvet\local\persistent\situation;
use mod_competvet\tests\test_helpers;
use stdClass;
use test_data_definition;

/**
 * Planning API test
 *
 * @package     mod_competvet
 * @copyright   2023 CALL Learning <contact@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class plannings_test extends advanced_testcase {
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
        $generator = $this->getDataGenerator();
        $competvetgenerator = $generator->get_plugin_generator('mod_competvet');
        $this->generates_definition($this->get_data_definition_set_1(), $generator, $competvetgenerator);
        $this->setAdminUser(); // Needed for report builder to work.
    }

    /**
     * Get all with planning for user
     *
     * @param string $username
     * @param array $expected
     * @return void
     * @dataProvider all_situations_with_planning
     * @covers       \mod_competvet\local\api\situations::get_all_situations_for
     */
    public function test_get_plannings_for_situation_id(string $username, bool $withfuture, array $expected) {
        $user = core_user::get_user_by_username($username);
        $situations = situation::get_all_situations_id_for($user->id);
        $allplannings = [];
        foreach ($situations as $situationid) {
            $situation = situation::get_record(['id' => $situationid]);
            $plannings = plannings::get_plannings_for_situation_id($situationid, $user->id, $withfuture);
            test_helpers::remove_elements_for_assertions($plannings, ['id']);
            $allplannings[$situation->get('shortname')] =
                array_merge($allplannings[$situation->get('shortname')] ?? [], $plannings);
        }
        ksort($allplannings);
        ksort($expected);
        $this->assertSame($expected, $allplannings);
    }
}
