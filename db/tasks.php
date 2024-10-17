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

/**
 * Scheduled task definitions for CompetVet
 *
 * Documentation: {@link https://moodledev.io/docs/apis/subsystems/task/scheduled}
 *
 * @package    mod_competvet
 * @category   task
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$tasks = [
    [
        'classname' => 'mod_competvet\task\end_of_planning',
        'blocking' => 0,
        'minute' => '16',
        'hour' => '0',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*',
    ],
    [
        'classname' => 'mod_competvet\task\items_todo',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '16',
        'day' => '*',
        // Run on Monday, Wednesday, and Friday
        'dayofweek' => '1,3,5',
        'month' => '*',
    ],
    [
        'classname' => 'mod_competvet\task\student_target',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '16',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*',
    ],
    [
        'classname' => 'mod_competvet\task\clear_pending_todos',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '2',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*',
    ],
];
