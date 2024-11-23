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
// phpcs:ignoreFile

use mod_competvet\local\persistent\planning;

defined('MOODLE_INTERNAL') || die();
$oneweek = 60 * 60 * 24 * 7; // 1 week in seconds.
$onemonth = $oneweek * 4; // 1 month in seconds.
$results = [];

// Results for student1 and observer1.
$results['student1results'] = [
    'SIT1' =>
        [
            [
                'startdate' => (string) planning::round_start_date($startdate),
                'enddate' => (string) planning::round_end_date(($startdate + $oneweek)),
                'session' => '2023',
                'groupname' => 'group 8.1',
            ],
        ],
    'SIT2' =>
        [
            [
                'startdate' => (string) planning::round_start_date($startdate),
                'enddate' => (string) planning::round_end_date(($startdate + $oneweek * 2)),
                'session' => '2023',
                'groupname' => 'group 8.1',
            ],
        ],
    'SIT3' =>
        [
            [
                'startdate' => (string) planning::round_start_date($startdate),
                'enddate' => (string) planning::round_end_date(($startdate + $oneweek)),
                'session' => '2023',
                'groupname' => 'group 8.1',
            ],
        ],
    'SIT4' => [],
    'SIT5' => [],
    'SIT6' => [],
    'SIT7' => [],
    'SIT8' => [],
    'SIT9' => [],
];
$results['student1resultswithfuture'] = [
    'SIT1' =>
        [
            [
                'startdate' => (string) planning::round_start_date($startdate),
                'enddate' => (string) planning::round_end_date(($startdate + $oneweek)),
                'session' => '2023',
                'groupname' => 'group 8.1',
            ],
            [
                'startdate' => (string) planning::round_start_date(($startdate + $onemonth * 12)),
                'enddate' => (string) planning::round_end_date(($startdate + $onemonth * 12 + $oneweek)),
                'session' => '2030',
                'groupname' => 'group 8.1',
            ],
        ],
    'SIT2' =>
        [
            [
                'startdate' => (string) planning::round_start_date(($startdate)),
                'enddate' => (string) planning::round_end_date(($startdate + $oneweek * 2)),
                'session' => '2023',
                'groupname' => 'group 8.1',
            ],
        ],
    'SIT3' =>
        [
            [
                'startdate' => (string) planning::round_start_date(($startdate)),
                'enddate' => (string) planning::round_end_date(($startdate + $oneweek)),
                'session' => '2023',
                'groupname' => 'group 8.1',
            ],
        ],
    'SIT4' =>
        [
            [
                'startdate' => (string) planning::round_start_date(($startdate + $onemonth * 3)),
                'enddate' => (string) planning::round_end_date(($startdate + $onemonth * 3 + $oneweek)),
                'session' => '2023',
                'groupname' => 'group 8.1',
            ],
        ],
    'SIT5' => [],
    'SIT6' => [],
    'SIT7' =>
        [
            [
                'startdate' => (string) planning::round_start_date(($startdate + $onemonth * 6)),
                'enddate' => (string) planning::round_end_date(($startdate + $onemonth * 6 + $oneweek)),
                'session' => '2023',
                'groupname' => 'group 8.1',
            ],
        ],
    'SIT8' => [],
    'SIT9' => [],
];
$results['observer1results'] = [
    'SIT1' =>
        [
            [
                'startdate' => (string) planning::round_start_date($startdate),
                'enddate' => (string) planning::round_end_date(($startdate + $oneweek)),
                'session' => '2023',
                'groupname' => 'group 8.1',
            ],
        ],
    'SIT2' =>
        [
            [
                'startdate' => (string) planning::round_start_date($startdate),
                'enddate' => (string) planning::round_end_date(($startdate + $oneweek * 2)),
                'session' => '2023',
                'groupname' => 'group 8.1',
            ],
        ],
    'SIT3' =>
        [
            [
                'startdate' => (string) planning::round_start_date(($startdate)),
                'enddate' => (string) planning::round_end_date(($startdate + $oneweek)),
                'session' => '2023',
                'groupname' => 'group 8.1',
            ],
        ],
];
$results['observer1resultswithfuture'] = [
    'SIT1' =>
        [
            [
                'startdate' => (string) planning::round_start_date($startdate),
                'enddate' => (string) planning::round_end_date(($startdate + $oneweek)),
                'session' => '2023',
                'groupname' => 'group 8.1',
            ],
            [
                'startdate' => (string) planning::round_start_date(($startdate + $oneweek)),
                'enddate' => (string) planning::round_end_date(($startdate + $oneweek * 2)),
                'session' => '2023',
                'groupname' => 'group 8.2',
            ],
            [
                'startdate' => (string) planning::round_start_date(($startdate + $onemonth * 12)),
                'enddate' => (string) planning::round_end_date(($startdate + $onemonth * 12 + $oneweek)),
                'session' => '2030',
                'groupname' => 'group 8.1',
            ],
        ],
    'SIT2' =>
        [
            [
                'startdate' => (string) planning::round_start_date($startdate),
                'enddate' => (string) planning::round_end_date(($startdate + $oneweek * 2)),
                'session' => '2023',
                'groupname' => 'group 8.1',
            ],
        ],
    'SIT3' =>
        [
            [
                'startdate' => (string) planning::round_start_date(($startdate)),
                'enddate' => (string) planning::round_end_date(($startdate + $oneweek)),
                'session' => '2023',
                'groupname' => 'group 8.1',
            ],
            [
                'startdate' => (string) planning::round_start_date(($startdate + $onemonth * 2 + $oneweek)),
                'enddate' => (string) planning::round_end_date(($startdate + $onemonth * 2 + $oneweek * 2)),
                'session' => '2023',
                'groupname' => 'group 8.2',
            ],
        ],
];
