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

use core_external\external_api;
use core_external\external_description;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use mod_competvet\competvet;
use mod_competvet\local\api\plannings;
use mod_competvet\local\persistent\planning;

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
            'userid' => new external_value(PARAM_INT, 'Orphaned user id', VALUE_REQUIRED),
            'groupid' => new external_value(PARAM_INT, 'Target group id', VALUE_REQUIRED),
            'planningid' => new external_value(PARAM_INT, 'Target planning id', VALUE_REQUIRED),
            'oldplanningid' => new external_value(PARAM_INT, 'Old planning id holding the orphaned records', VALUE_REQUIRED),
            'action' => new external_value(PARAM_TEXT, 'Fix action (orphanfix:add or orphanfix:move)', VALUE_REQUIRED),
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
     * Execute and fix the orphaned user.
     *
     * @param array $params The parameters.
     * @return array The result.
     * @throws \moodle_exception
     * @throws \invalid_parameter_exception
     */
    public static function execute(array $params): array {
        $params = self::validate_parameters(self::execute_parameters(), $params);

        $oldplanning = planning::get_record(['id' => $params['oldplanningid']]);
        if (!$oldplanning) {
            throw new \moodle_exception('invaliddata', 'competvet', '', 'oldplanningid');
        }
        $competvet = competvet::get_from_situation_id($oldplanning->get('situationid'));
        $context = $competvet->get_context();
        self::validate_context($context);

        // Each fix action is gated by the capability that matches what it actually does:
        // re-adding the user to a group is a group-management operation, while moving the
        // orphaned assessment records is an assessor operation.
        if ($params['action'] == 'orphanfix:move') {
            if (!has_capability('mod/competvet:cangrade', $context)) {
                throw new \moodle_exception('nopermission', 'competvet');
            }
        } else if ($params['action'] == 'orphanfix:add') {
            if (!has_capability('moodle/course:managegroups', $context)) {
                throw new \moodle_exception('nopermission', 'competvet');
            }
        } else {
            throw new \moodle_exception('invaliddata', 'competvet', '', 'action');
        }

        $result = plannings::fix_orphan_user(
            $params['userid'],
            $params['groupid'],
            $params['planningid'],
            $params['oldplanningid'],
            $params['action']
        );

        return [
            'result' => $result,
        ];
    }
}
