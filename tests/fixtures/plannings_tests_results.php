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
defined('MOODLE_INTERNAL') || die();

$results = [];
$results['student1results'] = [
    'SIT1' =>
        [
            [
                'startdate' => '1698793200',
                'enddate' => '1699398000',
                'session' => '2023',
                'groupname' => 'group 8.1',
            ],
        ],
    'SIT2' =>
        [
            [
                'startdate' => '1701385200',
                'enddate' => '1701990000',
                'session' => '2023',
                'groupname' => 'group 8.1',
            ],
        ],
    'SIT3' =>
        [
            [
                'startdate' => '1703977200',
                'enddate' => '1704582000',
                'session' => '2023',
                'groupname' => 'group 8.1',
            ],
        ],
    'SIT4' =>
        [
            [
                'startdate' => '1706569200',
                'enddate' => '1704582000',
                'session' => '2023',
                'groupname' => 'group 8.1',
            ],
        ],
    'SIT7' =>
        [
        ],
];
$results['student1resultswithfuture'] = [
    'SIT1' =>
        [
            [
                'startdate' => '1698793200',
                'enddate' => '1699398000',
                'session' => '2023',
                'groupname' => 'group 8.1',
            ],
            [
                'startdate' => '1901973463',
                'enddate' => '1902578263',
                'session' => '2030',
                'groupname' => 'group 8.1',
            ],
        ],
    'SIT2' =>
        [
            [
                'startdate' => '1701385200',
                'enddate' => '1701990000',
                'session' => '2023',
                'groupname' => 'group 8.1',
            ],
        ],
    'SIT3' =>
        [
            [
                'startdate' => '1703977200',
                'enddate' => '1704582000',
                'session' => '2023',
                'groupname' => 'group 8.1',
            ],
        ],
    'SIT4' =>
        [
            [
                'startdate' => '1706569200',
                'enddate' => '1704582000',
                'session' => '2023',
                'groupname' => 'group 8.1',
            ],
        ],
    'SIT7' =>
        [
            [
                'startdate' => '1714345200',
                'enddate' => '1704582000',
                'session' => '2023',
                'groupname' => 'group 8.1',
            ],
        ],
];
$results['observer1results'] = [
    'SIT1' =>
        [
            [
                'startdate' => '1698793200',
                'enddate' => '1699398000',
                'session' => '2023',
                'groupname' => 'group 8.1',
            ],
            [
                'startdate' => '1699398000',
                'enddate' => '1700002800',
                'session' => '2023',
                'groupname' => 'group 8.2',
            ],
        ],
    'SIT2' =>
        [
            [
                'startdate' => '1701385200',
                'enddate' => '1701990000',
                'session' => '2023',
                'groupname' => 'group 8.1',
            ],
        ],
    'SIT3' =>
        [
            [
                'startdate' => '1703977200',
                'enddate' => '1704582000',
                'session' => '2023',
                'groupname' => 'group 8.1',
            ],
            [
                'startdate' => '1704582000',
                'enddate' => '1705186800',
                'session' => '2023',
                'groupname' => 'group 8.2',
            ],
        ],
];
$results['observer1resultswithfuture'] = [
    'SIT1' =>
        [
            [
                'startdate' => '1698793200',
                'enddate' => '1699398000',
                'session' => '2023',
                'groupname' => 'group 8.1',
            ],
            [
                'startdate' => '1699398000',
                'enddate' => '1700002800',
                'session' => '2023',
                'groupname' => 'group 8.2',
            ],
            [
                'startdate' => '1901973463',
                'enddate' => '1902578263',
                'session' => '2030',
                'groupname' => 'group 8.1',
            ],
        ],
    'SIT2' =>
        [
            0 =>
                [
                    'startdate' => '1701385200',
                    'enddate' => '1701990000',
                    'session' => '2023',
                    'groupname' => 'group 8.1',
                ],
        ],
    'SIT3' =>
        [
            0 =>
                [
                    'startdate' => '1703977200',
                    'enddate' => '1704582000',
                    'session' => '2023',
                    'groupname' => 'group 8.1',
                ],
            1 =>
                [
                    'startdate' => '1704582000',
                    'enddate' => '1705186800',
                    'session' => '2023',
                    'groupname' => 'group 8.2',
                ],
        ],
];
