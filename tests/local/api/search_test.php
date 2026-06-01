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
#[\PHPUnit\Framework\Attributes\CoversClass(\mod_competvet\local\api\search::class)]
final class search_test extends advanced_testcase {
    use test_data_definition;

    /**
     * Data provider for search API
     *
     * @return \Generator
     */
    public static function data_provider_search_situations(): \Generator {
        $oneweek = 60 * 60 * 24 * 7; // 1 week in seconds.
        $startdate = self::get_start_date()->getTimestamp();
        yield from [
            'simple search' => [
                'SIT1',
                'student1',
                [
                    [
                        'type' => 'situation',
                        'description' => 'SIT1',
                        'identifier' => 'SIT1',
                        'additionalinfos' => [
                            'plannings' => [
                                [
                                    // Relative dates, so we can compare.
                                    'startdate' => planning::round_start_date($startdate),
                                    'enddate' => planning::round_end_date($startdate + $oneweek),
                                    'groupname' => 'group 8.1',
                                ],
                            ],
                        ],
                    ],
                    // No future situations.
                ],
            ],
            'simple search with lowercase' => [
                'sit1',
                'student1',
                [
                    [
                        'type' => 'situation',
                        'description' => 'SIT1',
                        'identifier' => 'SIT1',
                        'additionalinfos' => [
                            'plannings' => [
                                [
                                    'startdate' => planning::round_start_date($startdate),
                                    'enddate' => planning::round_end_date($startdate + $oneweek),
                                    'groupname' => 'group 8.1',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'simple search with lowercase (student2)' => [
                'sit1',
                'student2',
                [
                    [
                        'type' => 'situation',
                        'description' => 'SIT1',
                        'identifier' => 'SIT1',

                        'additionalinfos' => [
                            'plannings' => [
                                [
                                    'startdate' => planning::round_start_date($startdate),
                                    'enddate' => planning::round_end_date($startdate + $oneweek * 2),
                                    'groupname' => 'group 8.2',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Data provider for get_query
     *
     * @return \Generator
     */
    public static function data_provider_search_users(): \Generator {
        yield from [
            'simple search' => [
                'searchtext' => 'Observer',
                'currentuser' => 'student1',
                'expectedresults' => [
                    [
                        'type' => 'user',
                        'identifier' => 'observer2',
                        'description' => 'Observer Two OBSevérTWO',
                        'additionalinfos' => [
                            'roles' => ['observer'],
                            'situations' =>
                                [
                                    [
                                        'situation' => 'SIT1',
                                        'planningcount' => 1,
                                    ],
                                ],
                        ],
                    ],
                    [
                        'type' => 'user',
                        'identifier' => 'observer1',
                        'description' => 'Observer One OBSONE',
                        'additionalinfos' => [
                            'roles' => ['observer'],
                            'situations' =>
                                [
                                    [
                                        'situation' => 'SIT1',
                                        'planningcount' => 1,
                                    ],
                                    [
                                        'situation' => 'SIT2',
                                        'planningcount' => 1,
                                    ],
                                    [
                                        'situation' => 'SIT3',
                                        'planningcount' => 1,
                                    ],
                                ],
                        ],
                    ],
                ],
            ],
            'simple search with lowercase' => [
                'searchtext' => 'observer',
                'currentuser' => 'student1',
                'expectedresults' => [
                    [
                        'type' => 'user',
                        'identifier' => 'observer2',
                        'description' => 'Observer Two OBSevérTWO',
                        'additionalinfos' => [
                            'roles' => ['observer'],
                            'situations' =>
                                [
                                    [
                                        'situation' => 'SIT1',
                                        'planningcount' => 1,
                                    ],
                                ],
                        ],
                    ],
                    [
                        'type' => 'user',
                        'identifier' => 'observer1',
                        'description' => 'Observer One OBSONE',
                        'additionalinfos' => [
                            'roles' => ['observer'],
                            'situations' =>
                                [
                                    [
                                        'situation' => 'SIT1',
                                        'planningcount' => 1,
                                    ],
                                    [
                                        'situation' => 'SIT2',
                                        'planningcount' => 1,
                                    ],
                                    [
                                        'situation' => 'SIT3',
                                        'planningcount' => 1,
                                    ],
                                ],
                        ],
                    ],
                ],
            ],
            'simple search student2' => [
                'searchtext' => 'observer',
                'currentuser' => 'student2',
                'expectedresults' => [
                    [
                        'type' => 'user',
                        'identifier' => 'observer2',
                        'description' => 'Observer Two OBSevérTWO',
                        'additionalinfos' => [
                            'roles' => ['observer'],
                            'situations' =>
                                [
                                    [
                                        'situation' => 'SIT1',
                                        'planningcount' => 1,
                                    ],
                                ],
                        ],
                    ],
                    [
                        'type' => 'user',
                        'identifier' => 'observer1',
                        'description' => 'Observer One OBSONE',
                        'additionalinfos' => [
                            'roles' => ['observer'],
                            'situations' =>
                                [
                                    [
                                        'situation' => 'SIT1',
                                        'planningcount' => 1,
                                    ],
                                ],
                        ],
                    ],
                    [
                        'type' => 'user',
                        'identifier' => 'observer3',
                        'description' => 'Observer Three OBSe3',
                        'roles' => ['observer'],
                        'additionalinfos' => [
                            'roles' => ['observer'],
                            'situations' =>
                                [
                                    [
                                        'situation' => 'SIT4',
                                        'planningcount' => 1,
                                    ],
                                ],
                        ],
                    ],
                ],
            ],
            'simple search student2 for student' => [
                'searchtext' => 'student',
                'currentuser' => 'student2',
                'expectedresults' => [],
            ],
        ];
    }

    /**
     * Setup the test
     *
     * @return void
     */
    public function setUp(): void {
        global $DB;
        parent::setUp();
        $this->resetAfterTest();

        $this->setAdminUser(); // Needed for report builder to work.
        $generator = $this->getDataGenerator();
        $competvetgenerator = $generator->get_plugin_generator('mod_competvet');
        $startdate = $this->get_start_date();
        $this->generates_definition(
            $this->get_definition_set_search($startdate->getTimestamp()),
            $generator,
            $competvetgenerator
        );
        $this->set_current_date();

        // We need to make sure that users are set with the right data for search.
        $userdata = [
            'student1' => [
                'firstname' => 'Student One',
                'lastname' => 'Lastname One',
            ],
            'student2' => [
                'firstname' => 'Student Two',
                'lastname' => 'Lastname Two',
            ],
            'observer1' => [
                'firstname' => 'Observer One',
                'lastname' => 'OBSONE',
            ],
            'observer2' => [
                'firstname' => 'Observer Two',
                'lastname' => 'OBSevérTWO',
            ],
            'observer3' => [
                'firstname' => 'Observer Three',
                'lastname' => 'OBSe3',
            ],
        ];
        foreach ($userdata as $username => $data) {
            $user = $DB->get_record('user', ['username' => $username]);
            $DB->update_record('user', array_merge((array) $user, $data));
        }
    }

    /**
     * Test situation search
     *
     * @param string $searchtext
     * @param string $username
     * @param array $expectedresults
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('data_provider_search_situations')]
    public function test_planning_search(string $searchtext, string $username, array $expectedresults): void {
        $user = \core_user::get_user_by_username($username);
        $this->setUser($user); // User involved in the scenario.
        $returnval = search::search_query($searchtext, [search::TYPE_SITUATION]);
        $this->assertCount(count($expectedresults), $returnval);

        $filterout = fn($item) => [
            'type' => $item['type'],
            'description' => $item['description'],
            'identifier' => $item['identifier'],
            'additionalinfos' => [
                'plannings' => array_map(
                    fn($planning) => [
                        'startdate' => $planning['startdate'],
                        'enddate' => $planning['enddate'],
                        'groupname' => $planning['groupname'],
                    ],
                    $item['additionalinfos']['plannings']
                ),
            ],
        ];
        $this->assertEquals(
            array_map($filterout, $expectedresults),
            array_map($filterout, $returnval)
        );
    }

    /**
     * Test user search
     *
     * @param string $searchtext
     * @param string $currentuser
     * @param array $expectedresults
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('data_provider_search_users')]
    public function test_usersearch_search(string $searchtext, string $currentuser, array $expectedresults): void {
        global $DB;
        $user = \core_user::get_user_by_username($currentuser);
        $this->setUser($user); // User involved in the scenario.
        $returnval = search::search_query($searchtext, [search::TYPE_USER]);
        $this->assertCount(count($expectedresults), $returnval);

        foreach ($returnval as $index => $item) {
            $this->assertEquals(
                $expectedresults[$index]['type'],
                $item['type']
            );
            $this->assertEquals(
                $expectedresults[$index]['identifier'],
                $item['identifier']
            );
            $this->assertEquals(
                $expectedresults[$index]['additionalinfos']['roles'],
                $item['additionalinfos']['roles']
            );
            if (isset($expectedresults[$index]['additionalinfos'])) {
                foreach ($expectedresults[$index]['additionalinfos']['situations'] as $sitindex => $situationinfo) {
                    $this->assertEquals(
                        $situationinfo['situation'],
                        $item['additionalinfos']['situations'][$sitindex]['shortname']
                    );
                    $this->assertCount(
                        $situationinfo['planningcount'],
                        $item['additionalinfos']['situations'][$sitindex]['plannings']
                    );
                }
            }
        }
    }

    /**
     * Get definition set for search tests
     *
     * @param int $startdate
     * @return array
     */
    protected function get_definition_set_search(int $startdate) {
        $oneweek = 60 * 60 * 24 * 7; // 1 week in seconds.
        $onemonth = $oneweek * 4; // 1 month in seconds.
        return [
            'course 1' => [
                'users' => [
                    'student' => ['student1', 'student2'],
                    'observer' => ['observer1', 'observer2'],
                    'manager' => ['manager'],
                ],
                'groups' => [
                    'group 8.1' => [
                        'users' => ['student1'],
                    ],
                    'group 8.2' => [
                        'users' => ['student2'],
                    ],
                    'group 8.3' => [
                        'users' => [],
                    ],
                    'group 8.4' => [
                        'users' => [],
                    ],
                ],
                'activities' => [
                    'SIT1' => [
                        'category' => 'Y1',
                        'plannings' => [
                            [
                                'startdate' => $startdate,
                                'enddate' => $startdate + $oneweek,
                                'groupname' => 'group 8.1',
                                'session' => '2023',
                            ],
                            [
                                'startdate' => $startdate,
                                'enddate' => $startdate + $oneweek * 2,
                                'groupname' => 'group 8.2',
                                'session' => '2023',
                            ],
                            [
                                'startdate' => $startdate + $onemonth * 12, // Future time.
                                'enddate' => $startdate + $onemonth * 12 + $oneweek,
                                'groupname' => 'group 8.1',
                                'session' => '2030',
                            ],
                        ],
                    ],
                    'SIT2' => [
                        'category' => 'Y2',
                        'plannings' => [
                            [
                                'startdate' => $startdate,
                                'enddate' => $startdate + $oneweek * 2,
                                'groupname' => 'group 8.1',
                                'session' => '2023',
                            ],
                        ],
                    ],
                    'SIT3' => [
                        'category' => 'Y3',
                        'plannings' => [
                            [
                                'startdate' => $startdate,
                                'enddate' => $startdate + $oneweek,
                                'groupname' => 'group 8.1',
                                'session' => '2023',
                            ],
                        ],
                    ],
                ],
            ],
            'course 2' => [
                'users' => [
                    'student' => ['student2'],
                    'observer' => ['observer3'],
                ],
                'groups' => [
                    'group 8.1' => [
                        'users' => ['student2'],
                    ],
                ],
                'activities' => [
                    'SIT4' => [
                        'category' => 'Y1',
                        'plannings' => [
                            [
                                'startdate' => $startdate,
                                'enddate' => $startdate + $onemonth * 3 + $oneweek,
                                'groupname' => 'group 8.1',
                                'session' => '2023',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
