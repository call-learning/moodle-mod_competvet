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
            foreach ($data['activities'] as $situationname => $plannings) {
                $module = $generator->create_module('competvet', ['course' => $course->id, 'shortname' => $situationname]);
                $competvet = competvet::get_from_instance_id($module->id);
                $situation = $competvet->get_situation();
                foreach ($plannings as $planning) {
                    $groupid = groups_get_group_by_name($course->id, $planning['groupname']);
                    $competvetevalgenerator->create_planning([
                        'shortname' => $situationname,
                        'courseid' => $course->id,
                        'startdate' => $planning['startdate'],
                        'enddate' => $planning['startdate'],
                        'groupid' => $groupid,
                        'situationid' => $situation->get('id'),
                    ]);
                }
            }
        }
    }

    /**
     * Data definition
     */
    private function get_data_definition_set_1(): array {
        $startdate = 1698793200; // Date of 2023-01-01 08:00:00.
        $oneweek = 604800; // 1 week in seconds.
        $onemonth = 2592000; // 1 month in seconds.
        return [
            'course 1' => [
                'users' => [
                    'student' => ['student1', 'student2', 'student3'],
                    'observer' => ['observer1'],
                    'teacher' => ['teacher1'],
                ],
                'groups' => [
                    'group 8.1' => [
                        'users' => ['student1', 'student2'],
                    ],
                    'group 8.2' => [
                        'users' => ['student3'],
                    ],
                ],
                'activities' => [
                    'SIT1' => [
                        ['startdate' => $startdate, 'enddate' => $startdate + $oneweek, 'groupname' => 'group 8.1'],
                        ['startdate' => $startdate + $oneweek, 'enddate' => $startdate + $oneweek * 2, 'groupname' => 'group 8.2'],
                        ['startdate' => $startdate + $oneweek, 'enddate' => $startdate + $oneweek * 2, 'groupname' => 'group 8.1'],
                    ],
                    'SIT2' => [
                        ['startdate' => $startdate + $onemonth, 'enddate' => $startdate + $onemonth + $oneweek,
                            'groupname' => 'group 8.1',],
                        ['startdate' => $startdate + $onemonth + $oneweek, 'enddate' => $startdate + $onemonth + $oneweek * 2,
                            'groupname' => 'group 8.2',],
                    ],
                    'SIT3' => [
                        ['startdate' => $startdate + $onemonth * 2, 'enddate' => $startdate + $onemonth * 2 + $oneweek,
                            'groupname' => 'group 8.1',],
                        ['startdate' => $startdate + $onemonth * 2 + $oneweek,
                            'enddate' => $startdate + $onemonth * 2 + $oneweek * 2, 'groupname' => 'group 8.2',],
                    ],
                ],
            ],
            'course 2' => [
                'users' => [
                    'student' => ['student1', 'student2', 'student3', 'student4'],
                    'observer' => ['observer2'],
                    'teacher' => ['teacher2'],
                ],
                'groups' => [
                    'group 8.1' => [
                        'users' => ['student1', 'student2'],
                    ],
                    'group 8.3' => [
                        'users' => ['student3'],
                    ],
                    'group 8.4' => [
                        'users' => ['student4'],
                    ],
                ],
                'activities' => [
                    'SIT4' => [
                        ['startdate' => $startdate + $onemonth * 3, 'enddate' => $startdate + $onemonth * 2 + $oneweek,
                            'groupname' => 'group 8.1',],
                        ['startdate' => $startdate + $onemonth * 3 + $oneweek,
                            'enddate' => $startdate + $onemonth * 3 + $oneweek * 2, 'groupname' => 'group 8.3',],
                        ['startdate' => $startdate + $onemonth * 3 + $oneweek * 2,
                            'enddate' => $startdate + $onemonth * 3 + $oneweek * 3, 'groupname' => 'group 8.4',],
                    ],
                    'SIT5' => [
                        ['startdate' => $startdate + $onemonth * 4, 'enddate' => $startdate + $onemonth * 2 + $oneweek,
                            'groupname' => 'group 8.1',],
                        ['startdate' => $startdate + $onemonth * 4 + $oneweek,
                            'enddate' => $startdate + $onemonth * 4 + $oneweek * 2, 'groupname' => 'group 8.3',],
                        ['startdate' => $startdate + $onemonth * 4 + $oneweek * 2,
                            'enddate' => $startdate + $onemonth * 4 + $oneweek * 3, 'groupname' => 'group 8.4',],
                    ],
                    'SIT6' => [
                        ['startdate' => $startdate + $onemonth * 5, 'enddate' => $startdate + $onemonth * 2 + $oneweek,
                            'groupname' => 'group 8.1',],
                        ['startdate' => $startdate + $onemonth * 5 + $oneweek,
                            'enddate' => $startdate + $onemonth * 5 + $oneweek * 2, 'groupname' => 'group 8.3',],
                        ['startdate' => $startdate + $onemonth * 5 + $oneweek * 2,
                            'enddate' => $startdate + $onemonth * 5 + $oneweek * 3, 'groupname' => 'group 8.4',],
                    ],
                ],
            ],
            'course 3' => [
                'users' => [
                    'student' => ['student1', 'student2', 'student3', 'student4'],
                ],
                'groups' => [
                    'group 8.1' => [
                        'users' => ['student1', 'student2'],
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
                        ['startdate' => $startdate + $onemonth * 6, 'enddate' => $startdate + $onemonth * 2 + $oneweek,
                            'groupname' => 'group 8.1',],
                        ['startdate' => $startdate + $onemonth * 6 + $oneweek,
                            'enddate' => $startdate + $onemonth * 6 + $oneweek * 2, 'groupname' => 'group 8.3',],
                        ['startdate' => $startdate + $onemonth * 6 + $oneweek * 2,
                            'enddate' => $startdate + $onemonth * 6 + $oneweek * 3, 'groupname' => 'group 8.4',],
                    ],
                    'SIT8' => [
                        ['startdate' => $startdate + $onemonth * 7, 'enddate' => $startdate + $onemonth * 2 + $oneweek,
                            'groupname' => 'group 8.1',],
                        ['startdate' => $startdate + $onemonth * 7 + $oneweek,
                            'enddate' => $startdate + $onemonth * 7 + $oneweek * 2, 'groupname' => 'group 8.3',],
                        ['startdate' => $startdate + $onemonth * 7 + $oneweek * 2,
                            'enddate' => $startdate + $onemonth * 7 + $oneweek * 3, 'groupname' => 'group 8.4',],
                    ],
                    'SIT9' => [
                        ['startdate' => $startdate + $onemonth * 8, 'enddate' => $startdate + $onemonth * 2 + $oneweek,
                            'groupname' => 'group 8.1',],
                        ['startdate' => $startdate + $onemonth * 8 + $oneweek,
                            'enddate' => $startdate + $onemonth * 8 + $oneweek * 2, 'groupname' => 'group 8.3',],
                        ['startdate' => $startdate + $onemonth * 8 + $oneweek * 2,
                            'enddate' => $startdate + $onemonth * 8 + $oneweek * 3, 'groupname' => 'group 8.4',],
                    ],
                ],
            ],
        ];
    }
}