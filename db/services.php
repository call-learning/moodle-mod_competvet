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
 * CompetVet services
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
$functions = [
    'mod_competvet_get_user_list' => [
        'classname' => 'mod_competvet\\external\\get_user_list',
        'methodname' => 'execute',
        'description' => 'Get get_user_list for the given activity',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/competvet:view',
    ],
    'mod_competvet_get_situation_planning_info' => [
        'classname' => 'mod_competvet\\external\\get_planning_info',
        'methodname' => 'execute',
        'description' => 'Get situation planning info for the given user',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/competvet:view',
    ],
    'mod_competvet_delete_planning' => [
        'classname' => 'mod_competvet\\external\\delete_planning',
        'methodname' => 'execute',
        'description' => 'Delete a planning from its id',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/competvet:editplanning',
    ],
    'mod_competvet_eval_delete_observation' => [
        'classname' => 'mod_competvet\\external\\delete_observation',
        'methodname' => 'execute',
        'description' => 'Delete an observation from its id',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/competvet:editobservation',
    ],
    'mod_competvet_ask_eval_observation' => [
        'classname' => \mod_competvet\external\ask_eval_observation::class,
        'methodname' => 'execute',
        'description' => 'Ask for an observation and add it to the list of TODOs',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/competvet:view',
    ],
    'mod_competvet_get_user_profile' => [
        'classname' => \mod_competvet\external\user_info::class,
        'methodname' => 'execute',
        'description' => 'Get user profile information',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/competvet:view',
    ],
];
