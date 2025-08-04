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
use core_external\external_description;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_single_structure;
use core_external\external_multiple_structure;
use core_user;

/**
 * Class assign_roles
 *
 * @package    mod_competvet
 * @copyright  2025 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assign_roles extends external_api {
    /**
     * Assign or remove a role for a list of users in a course module context.
     *
     * @param array $userids Array of user IDs
     * @param int $cmid Course module ID
     * @param string $action 'add' or 'remove'
     * @param int $roleid Role ID
     * @return array Result array
     */
    public static function execute($userids, $cmid, $action, $roleid): array {
        global $DB;
        self::validate_context(context_system::instance());
        $results = [];
        $cm = get_coursemodule_from_id(null, $cmid, 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        require_capability('moodle/role:assign', $context);
        if (!$DB->record_exists('role', ['id' => $roleid])) {
            throw new \moodle_exception('invalidroleid', 'error', '', $roleid);
        }
        foreach ($userids as $userid) {
            if (!core_user::is_real_user($userid, true)) {
                $results[] = ['userid' => $userid, 'action' => 'invaliduser'];
                continue; // Skip if the user ID is not valid.
            }
            if ($action === 'add') {
                role_assign($roleid, $userid, $context->id);
                $results[] = ['userid' => $userid, 'action' => 'added'];
            } else if ($action === 'remove') {
                role_unassign($roleid, $userid, $context->id, '');
                $results[] = ['userid' => $userid, 'action' => 'removed'];
            } else {
                $results[] = ['userid' => $userid, 'action' => 'invalid'];
            }
        }
        return ['results' => $results];
    }

    /**
     * Parameters for execute webservice.
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'userids' => new external_multiple_structure(
                new external_value(PARAM_INT, 'User ID')
            ),
            'cmid' => new external_value(PARAM_INT, 'Course module ID'),
            'action' => new external_value(PARAM_ALPHA, 'Action (add or remove)'),
            'roleid' => new external_value(PARAM_INT, 'Role ID'),
        ]);
    }

    /**
     * Returns for execute webservice.
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'results' => new external_multiple_structure(
                new external_single_structure([
                    'userid' => new external_value(PARAM_INT, 'User ID'),
                    'action' => new external_value(PARAM_ALPHA, 'Result action'),
                ])
            )
        ]);
    }
}
