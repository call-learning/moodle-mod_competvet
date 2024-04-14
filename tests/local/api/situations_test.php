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
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/mod/competvet/tests/test_data_definition.php');

use advanced_testcase;
use core_user;
use mod_competvet\local\persistent\situation;
use mod_competvet\tests\test_helpers;
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
     * All for user provider with planning
     *
     * @return array[]
     */
    public static function all_for_user_provider_with_planning(): array {
        global $CFG;
        $results = [];
        include_once($CFG->dirroot . '/mod/competvet/tests/fixtures/situation_tests_results.php');
        return [
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
     * @dataProvider all_for_user_provider_with_planning
     * @covers       \mod_competvet\local\api\situations::get_all_situations_for
     */
    public function test_get_all_situations_with_planning_for(string $username, array $expected) {
        $user = core_user::get_user_by_username($username);
        $situations = situations::get_all_situations_with_planning_for($user->id);
        usort($situations, function($sit1, $sit2) {
            return $sit1['shortname'] <=> $sit2['shortname'];
        });
        usort($expected, function($sit1, $sit2) {
            return $sit1['shortname'] <=> $sit2['shortname'];
        });
        test_helpers::remove_elements_for_assertions($situations, ['id', 'intro', 'roles']);
        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($situations));
    }

    public function test_get_all_criteria() {
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $criteria = situations::get_all_criteria($situation->get('id'));
        $this->assertCount(40, $criteria);
        $this->assertEquals([
            'id' => 1,
            'label' => 'Savoir être',
            'idnumber' => 'Q001',
            'sort' => 1,
            'parentid' => 0,
            'parentlabel' => null,
            'parentidnumber' => null,
        ], $criteria[0]);
        $this->assertEquals([
            'id' => 3,
            'label' => 'Respect des interlocuteurs (clients, personnels, encadrants, pairs, ...)',
            'idnumber' => 'Q003',
            'sort' => 2,
            'parentid' => 1,
            'parentlabel' => 'Savoir être',
            'parentidnumber' => 'Q001',
        ], $criteria[8]);
    }
}
