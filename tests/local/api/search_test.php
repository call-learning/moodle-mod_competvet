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
use mod_competvet\local\persistent\planning;
use mod_competvet\tests\test_data_definition;

/**
 * Serch API test
 *
 * @package     mod_competvet
 * @copyright   2023 CALL Learning <contact@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class search_test extends advanced_testcase {
    use test_data_definition;

    /**
     * Data provider for search API
     *
     * @return array
     */
    public static function data_provider_search_situations(): array {
        $oneweek = 60 * 60 * 24 * 7; // 1 week in seconds.
        $onemonth = $oneweek * 4; // 1 month in seconds.
        return [
            'simple search' => [
                'SIT1',
                'student1',
                [
                    [
                        'type' => 'planning',
                        'description' => 'SIT1',
                        'identifier' => 'SIT1',
                        // Relative dates, so we can compare.
                        'startdate' => 0,
                        'enddate' => $oneweek,
                        'groupname' => 'group 8.1',
                    ],
                    // No future situations.
                ],
            ],
            'simple search with lowercase' => [
                'sit1',
                'student1',
                [
                    [
                        'type' => 'planning',
                        'description' => 'SIT1',
                        'identifier' => 'SIT1',
                        'startdate' => 0,
                        'enddate' => $oneweek,
                        'groupname' => 'group 8.1',
                    ],
                ],
            ],
        ];
    }


    /**
     * Data provider for get_query
     *
     * @return array
     */
    public static function data_provider_search_users(): array {
        $oneweek = 60 * 60 * 24 * 7; // 1 week in seconds.
        $onemonth = $oneweek * 4; // 1 month in seconds.
        return [
            'simple search' => [
                'Observer 1',
                'student1',
                [

                ],
            ],
            'simple search with lowercase' => [
                'observer 1',
                'student1',
                [
                ],
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
        global $CFG;
        //require_once($CFG->dirroot . '/search/tests/fixtures/testable_core_search.php');
        $this->setAdminUser(); // Needed for report builder to work.
        $this->prepare_scenario('set_2');
        $this->set_current_date();
    }

    /**
     * Test situation search
     *
     * @param string $searchtext
     * @param string $username
     * @param array $expectedresults
     * @return void
     * @covers       \mod_competvet\local\api\search::search_planning
     * @dataProvider data_provider_search_situations
     */
    public function test_planning_search(string $searchtext, string $username, array $expectedresults): void {
        $user = \core_user::get_user_by_username($username);
        $this->setUser($user); // User involved in the scenario.
        $returnval = search::search_query($searchtext);
        $this->assertCount(count($expectedresults), $returnval);

        $filterout = fn($item) => [
            'type' => $item['type'],
            'description' => $item['description'],
            'identifier' => $item['identifier'],
            'groupname' => $item['groupname'],
        ];
        $this->assertEquals(
            array_map($filterout, $expectedresults),
            array_map($filterout, $returnval)
        );

        // Now check dates separately with relative values.
        foreach ($expectedresults as $index => $expected) {
            $this->assertEquals(
                planning::round_start_date($this->startdate + $expected['startdate']),
                $returnval[$index]['startdate']
            );
            $this->assertEquals(
                planning::round_end_date($this->startdate + $expected['enddate']),
                $returnval[$index]['enddate']
            );
        }
    }

    /**
     * Test user search
     *
     * @param string $searchtext
     * @param string $username
     * @param array $expectedresults
     * @return void
     * @covers       \mod_competvet\local\api\search::search_users_in_situations
     * @dataProvider data_provider_search_users
     */
    public function test_usersearch_search(string $searchtext, string $username, array $expectedresults): void {
        $user = \core_user::get_user_by_username($username);
        $this->setUser($user); // User involved in the scenario.
        $returnval = search::search_query($searchtext);
        $this->assertCount(count($expectedresults), $returnval);

        $filterout = fn($item) => [
            'type' => $item['type'],
            'description' => $item['description'],
            'identifier' => $item['identifier'],
            'groupname' => $item['groupname'],
        ];
        $this->assertEquals(
            array_map($filterout, $expectedresults),
            array_map($filterout, $returnval)
        );

        // Now check dates separately with relative values.
        foreach ($expectedresults as $index => $expected) {
            $this->assertEquals(
                planning::round_start_date($this->startdate + $expected['startdate']),
                $returnval[$index]['startdate']
            );
            $this->assertEquals(
                planning::round_end_date($this->startdate + $expected['enddate']),
                $returnval[$index]['enddate']
            );
        }
    }
}
