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

use external_api;
use external_description;
use external_single_structure;
use external_function_parameters;
use external_value;
use mod_competvet\competvet;
use mod_competvet\local\persistent\planning;
use mod_competvet\local\api\plannings;
/**
 * Class fix_orphan_user
 *
 * @package    mod_competvet
 * @copyright  2025 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class fix_orphan_user extends external_api {
    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'User id', VALUE_REQUIRED),
            'groupid' => new external_value(PARAM_INT, 'Group id', VALUE_OPTIONAL),
            'planningid' => new external_value(PARAM_INT, 'Planning id', VALUE_OPTIONAL),
            'oldplanningid' => new external_value(PARAM_INT, 'Old planning id', VALUE_OPTIONAL),
            'action' => new external_value(PARAM_TEXT, 'Action', VALUE_OPTIONAL),
        ]);
    }

    /**
     * Returns description of method return value
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'result' => new external_value(PARAM_TEXT, 'Result of the operation'),
        ]);
    }

    /**
     * Execute and return plannings list
     *
     * @param int $userid
     * @param int $groupid
     * @param int $planningid
     * @param int $oldplanningid
     * @param string $action
     * @return array
     */
    public static function execute(int $userid, int $groupid, int $planningid, int $oldplanningid, string $action): array {
        $planning = planning::get_record(['id' => $planningid]);
        $competvet = competvet::get_from_situation_id($planning->get('situationid'));
        self::validate_context($competvet->get_context());
        if (!has_capability('mod/competvet:editplanning', $competvet->get_context())) {
            throw new \moodle_exception('nopermission', 'mod_competvet');
        }
        $result = plannings::fix_orphan_user($userid, $groupid, $planningid, $oldplanningid, $action);
        $data = [
            'result' => $result,
        ];
        return $data;
    }
}
