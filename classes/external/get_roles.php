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

// This is for 4.4 compatibility.
defined('MOODLE_INTERNAL') || die;
global $CFG;
require_once("$CFG->libdir/externallib.php");

use context_system;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * Class get_roles
 *
 * @package    mod_competvet
 * @copyright  2025 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_roles extends external_api {
    /**
     * Get roles and users for a course module context.
     *
     * @param int $cmid Course module ID
     * @return array
     */
    public static function execute($cmid): array {
        global $DB;
        self::validate_context(context_system::instance());
        $cm = get_coursemodule_from_id(null, $cmid, 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        require_capability('moodle/role:review', $context); // This capability is needed to review
        // roles (teachers and managers).
        $roles = get_assignable_roles($context, ROLENAME_SHORT);
        $result = [];
        foreach ($roles as $roleid => $roleshortname) {
            $roleentry = [
                'roleshortname' => $roleshortname,
                'users' => [],
            ];
            $userfieldsapi = \core_user\fields::for_name();
            $userfields = 'u.id, u.username' . $userfieldsapi->get_sql('u')->selects;
            $assignedusers = get_role_users($roleid, $context, false, $userfields);
            if ($assignedusers) {
                foreach ($assignedusers as $user) {
                    $roleentry['users'][] = ['username' => $user->username];
                }
            }
            $result[] = $roleentry;
        }
        return $result;
    }

    /**
     * Parameters for execute webservice.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID'),
        ]);
    }

    /**
     * Returns for execute webservice.
     *
     * @return external_multiple_structure
     */
    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure(
            new external_single_structure(
                [
                    'roleshortname' => new external_value(PARAM_ALPHANUMEXT, 'Role shortname'),
                    'users' => new external_multiple_structure(
                        new external_single_structure([
                            'username' => new external_value(PARAM_USERNAME, 'Username'),
                        ]),
                        'List of users assigned to the role',
                        VALUE_DEFAULT,
                        [],
                    ),
                ]
            )
        );
    }
}
