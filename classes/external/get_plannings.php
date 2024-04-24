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
use external_value;
use stdClass;
use external_single_structure;
use external_multiple_structure;
use mod_competvet\competvet;
use mod_competvet\local\api\plannings as plannings_api;

/**
 * Class get_plannings
 *
 * @package    mod_competvet
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_plannings extends external_api {
    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module id', VALUE_REQUIRED),
        ]);
    }

    /**
     * Execute and return plannings list
     *
     * @param int $cmid - The course module id
     * @return array $data - The plannings list
     * @throws \invalid_parameter_exception
     */
    public static function execute(int $cmid): array {
        global $USER;
        ['cmid' => $cmid] = self::validate_parameters(self::execute_parameters(), ['cmid' => $cmid]);
        $competvet = competvet::get_from_cmid($cmid);
        $context = $competvet->get_context();

        $coursegroups = groups_get_all_groups($context->get_course_context()->instanceid);
        $groups = array_map(function ($group) {
            return [
                'id' => $group->id,
                'name' => $group->name,
            ];
        }, $coursegroups);

        $plannings = plannings_api::get_plannings_for_situation_id($competvet->get_situation()->get('id'), $USER->id);
        // Covert the startdate and enddate to a human readable format using yyyy-MM-dd
        foreach ($plannings as $key => $planning) {
            $planninggroups = array_map(function ($group) use ($planning) {
                return [
                    'id' => $group['id'],
                    'name' => $group['name'],
                    'selected' => $group['id'] === $planning['groupid'],
                ];
            }, $groups);
            $plannings[$key]['startdate'] = userdate($planning['startdate'], '%Y-%m-%d');
            $plannings[$key]['enddate'] = userdate($planning['enddate'], '%Y-%m-%d');
            $plannings[$key]['groups'] = $planninggroups;
        }

        return [
            'plannings' => $plannings,
            'groups' => $groups,
            'version' => time(),
        ];
    }

    /**
     * Returns description of method return value
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'plannings' => new external_multiple_structure(new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Id', VALUE_REQUIRED),
                'startdate' => new external_value(PARAM_TEXT, 'Start date', VALUE_REQUIRED),
                'situationid' => new external_value(PARAM_INT, 'Situation id', VALUE_REQUIRED),
                'groupid' => new external_value(PARAM_INT, 'Group id', VALUE_REQUIRED),
                'enddate' => new external_value(PARAM_TEXT, 'End date', VALUE_REQUIRED),
                'groupname' => new external_value(PARAM_TEXT, 'Group name', VALUE_REQUIRED),
                'session' => new external_value(PARAM_TEXT, 'Session name', VALUE_REQUIRED),
                'groups' => new external_multiple_structure(new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Id', VALUE_REQUIRED),
                    'name' => new external_value(PARAM_TEXT, 'Name', VALUE_REQUIRED),
                    'selected' => new external_value(PARAM_BOOL, 'Selected', VALUE_OPTIONAL),
                ])),
            ])),
            'groups' => new external_multiple_structure(new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Id', VALUE_REQUIRED),
                'name' => new external_value(PARAM_TEXT, 'Name', VALUE_REQUIRED),
            ])),
            'version' => new external_value(PARAM_INT, 'Version', VALUE_REQUIRED),
        ]);
    }
}
