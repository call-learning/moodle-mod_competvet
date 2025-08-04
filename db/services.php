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
    'mod_competvet_get_student_list' => [
        'classname' => mod_competvet\external\get_student_list::class,
        'methodname' => 'execute',
        'description' => 'Get get_student_list for the given activity',
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
    'mod_competvet_get_plannings' => [
        'classname' => \mod_competvet\external\get_plannings::class,
        'methodname' => 'execute',
        'description' => 'Get plannings',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/competvet:editplanning',
    ],
    'mod_competvet_save_plannings' => [
        'classname' => \mod_competvet\external\save_plannings::class,
        'methodname' => 'execute',
        'description' => 'Save plannings',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/competvet:editplanning',
    ],
    'mod_competvet_get_json' => [
        'classname' => \mod_competvet\external\get_json::class,
        'methodname' => 'execute',
        'description' => 'Get the contents of a json file',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/competvet:view',
    ],
    'mod_competvet_manage_criteria' => [
        'classname' => \mod_competvet\external\manage_criteria::class,
        'methodname' => 'update',
        'description' => 'Update the criteria',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/competvet:editcriteria',
    ],
    'mod_competvet_get_criteria' => [
        'classname' => \mod_competvet\external\manage_criteria::class,
        'methodname' => 'get',
        'description' => 'get the criteria',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/competvet:view',
    ],
    'mod_competvet_manage_plannings' => [
        'classname' => \mod_competvet\external\manage_plannings::class,
        'methodname' => 'update',
        'description' => 'Update the plannings',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/competvet:editplanning',
    ],
    'mod_competvet_get_formdata' => [
        'classname' => \mod_competvet\external\formdata_handler::class,
        'methodname' => 'get',
        'description' => 'Get the formdata',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/competvet:view',
    ],
    'mod_competvet_store_formdata' => [
        'classname' => \mod_competvet\external\formdata_handler::class,
        'methodname' => 'store',
        'description' => 'Store the formdata',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/competvet:cangrade',
    ],
    'mod_competvet_get_global_grade' => [
        'classname' => \mod_competvet\external\manage_grade::class,
        'methodname' => 'get',
        'description' => 'Get the global grade',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/competvet:view',
    ],
    'mod_competvet_save_global_grade' => [
        'classname' => \mod_competvet\external\manage_grade::class,
        'methodname' => 'update',
        'description' => 'Update the global grade',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/competvet:cangrade',
    ],
    'mod_competvet_get_cases' => [
        'classname' => \mod_competvet\external\get_cases::class,
        'methodname' => 'execute',
        'description' => 'Get the cases',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/competvet:view',
    ],
    'mod_competvet_delete_entry' => [
        'classname' => \mod_competvet\external\delete_entry::class,
        'methodname' => 'execute',
        'description' => 'Delete a entry',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/competvet:cangrade',
    ],
    'mod_competvet_get_evaluation_results' => [
        'classname' => \mod_competvet\external\get_evaluations::class,
        'methodname' => 'execute',
        'description' => 'Get the evaluation results',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/competvet:view',
    ],
    'mod_competvet_get_certif_results' => [
        'classname' => \mod_competvet\external\get_certifications::class,
        'methodname' => 'execute',
        'description' => 'Get the certification results',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/competvet:view',
    ],
    'mod_competvet_get_suggested_grade' => [
        'classname' => \mod_competvet\external\get_suggested_grade::class,
        'methodname' => 'execute',
        'description' => 'Get the suggested grade',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/competvet:view',
    ],
    'mod_competvet_set_subgrade' => [
        'classname' => \mod_competvet\external\set_subgrade::class,
        'methodname' => 'execute',
        'description' => 'Set a subgrade',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/competvet:cangrade',
    ],
    'mod_competvet_get_subgrades' => [
        'classname' => \mod_competvet\external\get_subgrades::class,
        'methodname' => 'execute',
        'description' => 'Get the subgrades',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/competvet:view',
    ],
    'mod_competvet_get_todos' => [
        'classname' => \mod_competvet\external\get_todos::class,
        'methodname' => 'execute',
        'description' => 'Get todos for the given user',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/competvet:view',
    ],
    'mod_competvet_delete_todos' => [
        'classname' => \mod_competvet\external\delete_todos::class,
        'methodname' => 'execute',
        'description' => 'Delete selected todos',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/competvet:editplanning',
    ],
    'mod_competvet_get_lettergrade_scale' => [
        'classname' => \mod_competvet\external\get_lettergrade_scale::class,
        'methodname' => 'execute',
        'description' => 'Get the letter grade scale',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/competvet:view',
    ],
    'mod_competvet_add_remove_roles' => [
        'classname' => \mod_competvet\external\assign_roles::class,
        'methodname' => 'execute',
        'description' => 'Assign or remove a role for a list of users in a course module context',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'moodle/role:assign',
    ],
    'mod_competvet_get_roles' => [
        'classname' => \mod_competvet\external\get_roles::class,
        'methodname' => 'execute',
        'description' => 'Get the list of roles for a course module',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'moodle/role:assign',
    ],
];
