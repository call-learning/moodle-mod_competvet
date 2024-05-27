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
use mod_competvet\competvet;

/**
 * Competvet Trait for data test definition.
 */
trait test_data_definition {
    /**
     * Prepare scenario
     *
     * @param string $datasetname
     * @return void
     */
    public function prepare_scenario(string $datasetname): void {
        $generator = $this->getDataGenerator();
        $competvetgenerator = $generator->get_plugin_generator('mod_competvet');
        $startdate = new DateTime('last Monday');
        $this->generates_definition(
            $this->{'get_data_definition_' . $datasetname}($startdate->getTimestamp()),
            $generator,
            $competvetgenerator
        );
    }

    /**
     * Generates instances and modules
     *
     * @param array $datadefinition
     * @param object $generator
     * @param object $competvetevalgenerator
     * @return void
     */
    public function generates_definition(array $datadefinition, object $generator, object $competvetevalgenerator): void {
        $users = [];
        foreach ($datadefinition as $coursename => $data) {
            $course = $generator->create_course(['shortname' => $coursename]);
            foreach ($data['users'] as $role => $usernames) {
                foreach ($usernames as $username) {
                    if (empty($users[$username])) {
                        $users[$username] = $generator->create_user(['username' => $username]);
                    }
                    $generator->enrol_user($users[$username]->id, $course->id, $role);
                }
            }
            foreach ($data['groups'] as $groupname => $groupdata) {
                $group = $generator->create_group(['courseid' => $course->id, 'name' => $groupname]);
                foreach ($groupdata['users'] as $username) {
                    $generator->create_group_member(['groupid' => $group->id, 'userid' => $users[$username]->id]);
                }
            }
            foreach ($data['activities'] as $situationname => $situationinfo) {
                $situationmodule = [...$situationinfo];
                $situationmodule['course'] = $course->id;
                $situationmodule['shortname'] = $situationname;
                $situationmodule['name'] = $situationname;
                unset($situationmodule['plannings']);

                $module = $generator->create_module('competvet', $situationmodule);
                $competvet = competvet::get_from_instance_id($module->id);
                $situation = $competvet->get_situation();
                foreach ($situationinfo['plannings'] as $planning) {
                    $groupid = groups_get_group_by_name($course->id, $planning['groupname']);
                    $competvetevalgenerator->create_planning([
                        'courseid' => $course->id,
                        'startdate' => $planning['startdate'],
                        'enddate' => $planning['enddate'],
                        'groupid' => $groupid,
                        'situationid' => $situation->get('id'),
                        'session' => $planning['session'],
                    ]);
                }
            }
        }
    }

    /**
     * Data definition
     */
    private function get_data_definition_set_1(int $startdate): array {
        $oneweek = 60 * 60 * 24 * 7; // 1 week in seconds.
        $onemonth = $oneweek * 4; // 1 month in seconds.
        return [
            'course 1' => [
                'users' => [
                    'student' => ['student1', 'student2'],
                    'observer' => ['observer1', 'observerandevaluator'],
                    'teacher' => ['teacher1'],
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
                        'situationtags' => ['y:1'],
                        'plannings' => [
                            [
                                'startdate' => $startdate,
                                'enddate' => $startdate + $oneweek,
                                'groupname' => 'group 8.1',
                                'session' => '2023',
                            ],
                            [
                                'startdate' => $startdate + $oneweek,
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
                        'situationtags' => ['y:2'],
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
                        'situationtags' => ['y:3'],
                        'plannings' => [
                            [
                                'startdate' => $startdate,
                                'enddate' => $startdate + $oneweek,
                                'groupname' => 'group 8.1',
                                'session' => '2023',
                            ],
                            [
                                'startdate' => $startdate + $onemonth * 2 + $oneweek,
                                'enddate' => $startdate + $onemonth * 2 + $oneweek * 2,
                                'groupname' => 'group 8.2',
                                'session' => '2023',
                            ],
                        ],
                    ],
                ],
            ],
            'course 2' => [
                'users' => [
                    'student' => ['student1', 'student2', 'student3', 'student4'],
                    'observer' => ['observer2', 'observerandevalandevaluator'],
                    'evaluator' => ['observerandevalandevaluator'],
                    'teacher' => ['teacher2'],
                ],
                'groups' => [
                    'group 8.1' => [
                        'users' => ['student1', 'student2'],
                    ],
                    'group 8.2' => [
                        'users' => ['student3'],
                    ],
                    'group 8.3' => [
                        'users' => ['student4'],
                    ],
                ],
                'activities' => [
                    'SIT4' => [
                        'situationtags' => ['y:1'],
                        'plannings' => [
                            [
                                'startdate' => $startdate + $onemonth * 3,
                                'enddate' => $startdate + $onemonth * 3 + $oneweek,
                                'groupname' => 'group 8.1',
                                'session' => '2023',
                            ],
                            [
                                'startdate' => $startdate + $onemonth * 3 + $oneweek,
                                'enddate' => $startdate + $onemonth * 3 + $oneweek * 2,
                                'groupname' => 'group 8.3',
                                'session' => '2023',
                            ],
                        ],
                    ],
                    'SIT5' => [
                        'situationtags' => ['y:2'],
                        'plannings' => [
                            [
                                'startdate' => $startdate + $onemonth * 4,
                                'enddate' => $startdate + $onemonth * 4 + $oneweek,
                                'groupname' => 'group 8.2',
                                'session' => '2023',
                            ],
                            [
                                'startdate' => $startdate + $onemonth * 4 + $oneweek,
                                'enddate' => $startdate + $onemonth * 4 + $oneweek * 2,
                                'groupname' => 'group 8.3',
                                'session' => '2023',
                            ],
                        ],
                    ],
                    'SIT6' => [
                        'situationtags' => ['y:3'],
                        'plannings' => [
                            [
                                'startdate' => $startdate + $onemonth * 5,
                                'enddate' => $startdate + $onemonth * 5 + $oneweek,
                                'groupname' => 'group 8.3',
                                'session' => '2023',
                            ],
                        ],
                    ],
                ],
            ],
            'course 3' => [
                'users' => [
                    'student' => ['student1', 'student2', 'student3', 'student4', 'studentandobserver'],
                    'observer' => ['observer2', 'studentandobserver'],
                    'evaluator' => ['observerandevaluator'],
                ],
                'groups' => [
                    'group 8.1' => [
                        'users' => ['student1', 'student2', 'studentandobserver'],
                    ],
                    'group 8.3' => [
                        'users' => ['student3'],
                    ],
                    'group 8.4' => [
                        'users' => ['student4'],
                    ],
                ],
                'activities' => [
                    'SIT7' => [
                        'situationtags' => ['y:1'],
                        'plannings' => [
                            [
                                'startdate' => $startdate + $onemonth * 6,
                                'enddate' => $startdate + $onemonth * 6 + $oneweek,
                                'groupname' => 'group 8.1',
                                'session' => '2023',
                            ],
                        ],
                    ],
                    'SIT8' => [
                        'situationtags' => ['y:2'],
                        'plannings' => [
                            [
                                'startdate' => $startdate + $onemonth * 7,
                                'enddate' => $startdate + $onemonth * 7 + $oneweek,
                                'groupname' => 'group 8.3',
                                'session' => '2023',
                            ],
                            [
                                'startdate' => $startdate + $onemonth * 12, // Future time.
                                'enddate' => $startdate + $onemonth * 12 + $oneweek,
                                'session' => '2030',
                                'groupname' => 'group 8.3',
                            ],

                        ],
                    ],
                    'SIT9' => [
                        'situationtags' => ['y:3'],
                        'plannings' => [
                            [
                                'startdate' => $startdate + $onemonth * 8,
                                'enddate' => $startdate + $onemonth * 8 + $oneweek,
                                'groupname' => 'group 8.4',
                                'session' => '2023',
                            ],
                            [
                                'startdate' => $startdate + $onemonth * 12, // Future time.
                                'enddate' => $startdate + $onemonth * 12 + $oneweek,
                                'groupname' => 'group 8.4',
                                'session' => '2030',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
    /**
     * Data definition
     */
    private function get_data_definition_set_2(int $startdate): array {
        $oneweek = 60 * 60 * 24 * 7; // 1 week in seconds.
        $onemonth = $oneweek * 4; // 1 month in seconds.
        return [
            'course 1' => [
                'users' => [
                    'student' => ['student1', 'student2'],
                    'observer' => ['observer1', 'observer2'],
                    'teacher' => ['teacher1'],
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
                        'situationtags' => ['y:1'],
                        'plannings' => [
                            [
                                'startdate' => $startdate,
                                'enddate' => $startdate + $oneweek,
                                'groupname' => 'group 8.1',
                                'session' => '2023',
                            ],
                            [
                                'startdate' => $startdate + $oneweek,
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
                ],
            ],
        ];
    }
}
