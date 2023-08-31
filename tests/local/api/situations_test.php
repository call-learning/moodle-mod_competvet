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
use mod_competvet\local\api\situations;
use stdClass;
use test_data_definition;

/**
 * Situations API test
 *
 * @package     mod_competvet
 * @copyright   2023 CALL Learning <contact@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class situations_test extends advanced_testcase {
    use test_data_definition;

    /**
     * @var stdClass $courses
     */
    protected $courses;

    /**
     * All for user provider
     *
     * @return array[]
     */
    public static function all_for_user_provider(): array {
        return [
            'student situations' => ['student1', ['SIT1', 'SIT2', 'SIT3', 'SIT4', 'SIT5', 'SIT6', 'SIT7', 'SIT8', 'SIT9']],
            'observer1 situations' => ['observer1', ['SIT1', 'SIT2', 'SIT3']],
            'observer2 situations' => ['observer2', ['SIT4', 'SIT5', 'SIT6']],
            'teacher1 situations' => ['teacher1', ['SIT1', 'SIT2', 'SIT3']],
        ];
    }

    /**
     * All for user provider with planning
     *
     * @return array[]
     */
    public static function all_for_user_provider_with_planning(): array {
        //@codingStandardsIgnoreStart
        return [
            'student situations' => ['student1',
                '[{"shortname":"SIT1","session":"","evalnum":"1","autoevalnum":"1","description":"<div class=\"no-overflow\">Test competvet 1<\/div>","name":"CompetVet 1","roles":"[\"student\"]","plannings":[{"startdate":"1698793200","enddate":"1698793200","groupname":"group 8.1"},{"startdate":"1699398000","enddate":"1699398000","groupname":"group 8.2"},{"startdate":"1699398000","enddate":"1699398000","groupname":"group 8.1"}]},{"shortname":"SIT2","session":"","evalnum":"1","autoevalnum":"1","description":"<div class=\"no-overflow\">Test competvet 2<\/div>","name":"CompetVet 2","roles":"[\"student\"]","plannings":[{"startdate":"1701385200","enddate":"1701385200","groupname":"group 8.1"},{"startdate":"1701990000","enddate":"1701990000","groupname":"group 8.2"}]},{"shortname":"SIT3","session":"","evalnum":"1","autoevalnum":"1","description":"<div class=\"no-overflow\">Test competvet 3<\/div>","name":"CompetVet 3","roles":"[\"student\"]","plannings":[{"startdate":"1703977200","enddate":"1703977200","groupname":"group 8.1"},{"startdate":"1704582000","enddate":"1704582000","groupname":"group 8.2"}]},{"shortname":"SIT4","session":"","evalnum":"1","autoevalnum":"1","description":"<div class=\"no-overflow\">Test competvet 4<\/div>","name":"CompetVet 4","roles":"[\"student\"]","plannings":[{"startdate":"1706569200","enddate":"1706569200","groupname":"group 8.1"},{"startdate":"1707174000","enddate":"1707174000","groupname":"group 8.3"},{"startdate":"1707778800","enddate":"1707778800","groupname":"group 8.4"}]},{"shortname":"SIT5","session":"","evalnum":"1","autoevalnum":"1","description":"<div class=\"no-overflow\">Test competvet 5<\/div>","name":"CompetVet 5","roles":"[\"student\"]","plannings":[{"startdate":"1709161200","enddate":"1709161200","groupname":"group 8.1"},{"startdate":"1709766000","enddate":"1709766000","groupname":"group 8.3"},{"startdate":"1710370800","enddate":"1710370800","groupname":"group 8.4"}]},{"shortname":"SIT6","session":"","evalnum":"1","autoevalnum":"1","description":"<div class=\"no-overflow\">Test competvet 6<\/div>","name":"CompetVet 6","roles":"[\"student\"]","plannings":[{"startdate":"1711753200","enddate":"1711753200","groupname":"group 8.1"},{"startdate":"1712358000","enddate":"1712358000","groupname":"group 8.3"},{"startdate":"1712962800","enddate":"1712962800","groupname":"group 8.4"}]},{"shortname":"SIT7","session":"","evalnum":"1","autoevalnum":"1","description":"<div class=\"no-overflow\">Test competvet 7<\/div>","name":"CompetVet 7","roles":"[\"student\"]","plannings":[{"startdate":"1714345200","enddate":"1714345200","groupname":"group 8.1"},{"startdate":"1714950000","enddate":"1714950000","groupname":"group 8.3"},{"startdate":"1715554800","enddate":"1715554800","groupname":"group 8.4"}]},{"shortname":"SIT8","session":"","evalnum":"1","autoevalnum":"1","description":"<div class=\"no-overflow\">Test competvet 8<\/div>","name":"CompetVet 8","roles":"[\"student\"]","plannings":[{"startdate":"1716937200","enddate":"1716937200","groupname":"group 8.1"},{"startdate":"1717542000","enddate":"1717542000","groupname":"group 8.3"},{"startdate":"1718146800","enddate":"1718146800","groupname":"group 8.4"}]},{"shortname":"SIT9","session":"","evalnum":"1","autoevalnum":"1","description":"<div class=\"no-overflow\">Test competvet 9<\/div>","name":"CompetVet 9","roles":"[\"student\"]","plannings":[{"startdate":"1719529200","enddate":"1719529200","groupname":"group 8.1"},{"startdate":"1720134000","enddate":"1720134000","groupname":"group 8.3"},{"startdate":"1720738800","enddate":"1720738800","groupname":"group 8.4"}]}]'
            ],
            'observer1 situations' => ['observer1',
                '[{"shortname":"SIT1","session":"","evalnum":"1","autoevalnum":"1","description":"<div class=\"no-overflow\">Test competvet 1<\/div>","name":"CompetVet 1","roles":"[\"observer\"]","plannings":[{"startdate":"1698793200","enddate":"1698793200","groupname":"group 8.1"},{"startdate":"1699398000","enddate":"1699398000","groupname":"group 8.2"},{"startdate":"1699398000","enddate":"1699398000","groupname":"group 8.1"}]},{"shortname":"SIT2","session":"","evalnum":"1","autoevalnum":"1","description":"<div class=\"no-overflow\">Test competvet 2<\/div>","name":"CompetVet 2","roles":"[\"observer\"]","plannings":[{"startdate":"1701385200","enddate":"1701385200","groupname":"group 8.1"},{"startdate":"1701990000","enddate":"1701990000","groupname":"group 8.2"}]},{"shortname":"SIT3","session":"","evalnum":"1","autoevalnum":"1","description":"<div class=\"no-overflow\">Test competvet 3<\/div>","name":"CompetVet 3","roles":"[\"observer\"]","plannings":[{"startdate":"1703977200","enddate":"1703977200","groupname":"group 8.1"},{"startdate":"1704582000","enddate":"1704582000","groupname":"group 8.2"}]}]'
            ],
            'observer2 situations' => ['observer2',
                '[{"shortname":"SIT4","session":"","evalnum":"1","autoevalnum":"1","description":"<div class=\"no-overflow\">Test competvet 4<\/div>","name":"CompetVet 4","roles":"[\"observer\"]","plannings":[{"startdate":"1706569200","enddate":"1706569200","groupname":"group 8.1"},{"startdate":"1707174000","enddate":"1707174000","groupname":"group 8.3"},{"startdate":"1707778800","enddate":"1707778800","groupname":"group 8.4"}]},{"shortname":"SIT5","session":"","evalnum":"1","autoevalnum":"1","description":"<div class=\"no-overflow\">Test competvet 5<\/div>","name":"CompetVet 5","roles":"[\"observer\"]","plannings":[{"startdate":"1709161200","enddate":"1709161200","groupname":"group 8.1"},{"startdate":"1709766000","enddate":"1709766000","groupname":"group 8.3"},{"startdate":"1710370800","enddate":"1710370800","groupname":"group 8.4"}]},{"shortname":"SIT6","session":"","evalnum":"1","autoevalnum":"1","description":"<div class=\"no-overflow\">Test competvet 6<\/div>","name":"CompetVet 6","roles":"[\"observer\"]","plannings":[{"startdate":"1711753200","enddate":"1711753200","groupname":"group 8.1"},{"startdate":"1712358000","enddate":"1712358000","groupname":"group 8.3"},{"startdate":"1712962800","enddate":"1712962800","groupname":"group 8.4"}]}]'
            ],
            'teacher1 situations' => ['teacher1',
                '[{"shortname":"SIT1","session":"","evalnum":"1","autoevalnum":"1","description":"<div class=\"no-overflow\">Test competvet 1<\/div>","name":"CompetVet 1","roles":"[\"teacher\"]","plannings":[{"startdate":"1698793200","enddate":"1698793200","groupname":"group 8.1"},{"startdate":"1699398000","enddate":"1699398000","groupname":"group 8.2"},{"startdate":"1699398000","enddate":"1699398000","groupname":"group 8.1"}]},{"shortname":"SIT2","session":"","evalnum":"1","autoevalnum":"1","description":"<div class=\"no-overflow\">Test competvet 2<\/div>","name":"CompetVet 2","roles":"[\"teacher\"]","plannings":[{"startdate":"1701385200","enddate":"1701385200","groupname":"group 8.1"},{"startdate":"1701990000","enddate":"1701990000","groupname":"group 8.2"}]},{"shortname":"SIT3","session":"","evalnum":"1","autoevalnum":"1","description":"<div class=\"no-overflow\">Test competvet 3<\/div>","name":"CompetVet 3","roles":"[\"teacher\"]","plannings":[{"startdate":"1703977200","enddate":"1703977200","groupname":"group 8.1"},{"startdate":"1704582000","enddate":"1704582000","groupname":"group 8.2"}]}]'
            ],
        ];
        //@codingStandardsIgnoreEnd
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
        $situations = situations::get_all_situations_for($user->id);
        $situationssn = array_map(function($situation) {
            return $situation->get('shortname');
        }, $situations);
        sort($situationssn);
        $this->assertEquals($expected, array_values($situationssn), "Expected situations for user $username are different.");
    }

    /**
     * Get all with planning for user
     *
     * @param string $username
     * @param array $expected
     * @return void
     * @dataProvider all_for_user_provider_with_planning
     * @covers       \mod_competvet\local\api\situations::get_all_situations_for
     */
    public function test_get_all_situations_with_planning_for($username, $expected) {
        $user = core_user::get_user_by_username($username);
        $situations = situations::get_all_situations_with_planning_for($user->id);
        usort($situations, function($sit1, $sit2) {
            return $sit1->shortname <=> $sit2->shortname;
        });
        $this->remove_ids_for_assertions($situations);
        $this->assertEquals($expected, json_encode($situations));
    }

    private function remove_ids_for_assertions(&$record) {
        if (is_scalar($record)) {
            return;
        }
        foreach ($record as $field => $value) {
            if (str_ends_with($field, 'id')) {
                unset($record->$field);
            }
            if (is_array($value)) {
                foreach ($value as &$subrecord) {
                    $this->remove_ids_for_assertions($subrecord);
                }
            }
            $this->remove_ids_for_assertions($value);
        }
    }
}
