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
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use tool_brickfield\local\areas\core_course\fullname;

/**
 * Class get_
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_user_list extends external_api {
    /**
     * @var string[] Valid role archetypes
     */
    const VALID_ROLE_ARCHETYPES = [
        'student',
        'teacher',
    ];

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course Module Id', VALUE_REQUIRED),
            'roletype' => new external_value(PARAM_TEXT, 'Role type', VALUE_OPTIONAL, 'student'),
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
                    'id' => new external_value(PARAM_INT, 'User id'),
                    'firstname' => new external_value(PARAM_TEXT, 'User firstname'),
                    'lastname' => new external_value(PARAM_TEXT, 'User lastname'),
                    'fullname' => new external_value(PARAM_TEXT, 'User lastname'),
                    'email' => new external_value(PARAM_TEXT, 'User email'),
                    'pictureurl' => new external_value(PARAM_URL, 'User picture url'),
                ])
            ),
        ]);
    }

    /**
     * Execute and return student list
     *
     * @param int $cmid - Course Module Id
     * @param string|null $roletype - Role type
     * @return array|array[]
     * @throws \invalid_parameter_exception
     */
    public static function execute(int $cmid, ?string $roletype) {

        $cm = get_coursemodule_from_id('competvet', $cmid, 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context(\context_module::instance($cmid));
        // Validate roles.
        if (!in_array($roletype, self::VALID_ROLE_ARCHETYPES)) {
            throw new \invalid_parameter_exception('Invalid role type');
        }
        // Now, can this user view other users.
        course_require_view_participants($context->get_parent_context());
        $roles = get_archetype_roles($roletype ?? 'student');
        $users = get_role_users(array_keys($roles), $context, true);
        $parsedusers = array_map(function ($user) {
            global $PAGE;
            $user = \core_user::get_user($user->id);
            $userpicture = new \user_picture($user);
            $userpicture->size = 100;
            $userpicture->link = false;
            $userpicture->alttext = false;
            $pictureurl = $userpicture->get_url($PAGE);
            return [
                'id' => $user->id,
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'fullname' => fullname($user),
                'email' => $user->email,
                'pictureurl' => $pictureurl->out(),
            ];
        }, $users);
        return [
            'users' => $parsedusers,
        ];
    }
}
