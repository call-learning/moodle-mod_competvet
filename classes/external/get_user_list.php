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
namespace mod_competvet\external;

use external_api;
use context_system;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use tool_brickfield\local\areas\core_course\fullname;
use mod_competvet\local\api\plannings;


/**
 * Class get_
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_user_list extends external_api {

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'planningid' => new external_value(PARAM_INT, 'The planning id', VALUE_REQUIRED),
        ]);
    }

    /**
     * Returns description of method return value
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'users' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'The user id'),
                    'fullname' => new external_value(PARAM_TEXT, 'The user full name'),
                    'userpictureurl' => new external_value(PARAM_TEXT, 'The user picture url'),
                    'role' => new external_value(PARAM_TEXT, 'The user role'),
                ])
            ),
        ]);
    }

    /**
     * Execute and return student list
     *
     * @param int $planningid - The planning id
     * @return array|array[]
     * @throws \invalid_parameter_exception
     */
    public static function execute(int $planningid) {
        self::validate_context(context_system::instance());
        $userswithinfo = plannings::get_users_infos_for_planning_id($planningid);
        $users = [];
        foreach ($userswithinfo['students'] as $user) {
            $users[] = [
                'id' => $user['userinfo']['id'],
                'fullname' => $user['userinfo']['fullname'],
                'userpictureurl' => $user['userinfo']['userpictureurl'],
                'role' => $user['userinfo']['role'],
            ];
        }

        return [
            'users' => $users,
        ];
    }
}
