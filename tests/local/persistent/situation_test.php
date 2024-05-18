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
namespace mod_competvet\local\persistent;
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/mod/competvet/tests/test_data_definition.php');

use advanced_testcase;
use core_user;
use DateTime;
use test_data_definition;

/**
 * Situations API persistent test
 *
 * @package     mod_competvet
 * @copyright   2023 CALL Learning <contact@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class situation_test extends advanced_testcase {
    use test_data_definition;

    /**
     * All for user provider
     *
     * @return array[]
     */
    public static function all_for_user_provider(): array {
        return [
            'student1 situations' => ['student1', ['SIT1', 'SIT2', 'SIT3', 'SIT4', 'SIT7']],
            'student2 situations' => ['student2', ['SIT1', 'SIT3', 'SIT4', 'SIT7']],
            'observer1 situations' => ['observer1', ['SIT1', 'SIT2', 'SIT3']],
            'observer2 situations' => ['observer2', ['SIT4', 'SIT5', 'SIT6', 'SIT7', 'SIT8', 'SIT9']],
            'teacher1 situations' => ['teacher1', ['SIT1', 'SIT2', 'SIT3']],
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
    }

    /**
     * Get all for user
     *
     * @param string $username
     * @param array $expected
     * @return void
     * @dataProvider all_for_user_provider
     * @covers       \mod_competvet\local\api\situations::get_all_situations_for
     */
    public function test_get_all_situation_for($username, $expected) {
        $user = core_user::get_user_by_username($username);
        $situations = situation::get_all_situations_id_for($user->id);
        $situationssn = array_map(function ($situationid) {
            $situation = situation::get_record(['id' => $situationid]);
            return $situation->get('shortname');
        }, $situations);
        sort($situationssn);
        $this->assertEquals($expected, array_values($situationssn), "Expected situations for user $username are different.");
    }
}
