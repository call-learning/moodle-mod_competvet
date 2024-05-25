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

use context_system;
use external_api;
use external_description;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;
use external_warnings;
use mod_competvet\local\api\plannings;

define('COMPETVET_CRITERIA_EVALUATION', 1);
define('COMPETVET_CRITERIA_CERTIFICATION', 2);
define('COMPETVET_CRITERIA_LIST', 3);

/**
 * Class manage_plannings
 * Webservice class for managing criteria
 *
 * @package    mod_competvet
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manage_plannings extends external_api {
    /**
     * Returns description of method parameters. This will be used to validate the JSON data sent to the external function. It
     * Will need to allow the JSON objects for $exampleJsonEval, $exampleJsonCert and $exampleJsonList and it will include the
     * gridid and the type of criteria to manage.
     *
     * @return external_function_parameters
     */
    public static function update_parameters(): external_function_parameters {
        return new external_function_parameters([
            'plannings' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Id', VALUE_REQUIRED),
                    'situationid' => new external_value(PARAM_INT, 'Situation id', VALUE_REQUIRED),
                    'groupid' => new external_value(PARAM_INT, 'Group id', VALUE_REQUIRED),
                    'startdate' => new external_value(PARAM_TEXT, 'Start date', VALUE_REQUIRED),
                    'enddate' => new external_value(PARAM_TEXT, 'End date', VALUE_REQUIRED),
                    'session' => new external_value(PARAM_TEXT, 'Session name', VALUE_REQUIRED),
                    'haschanged' => new external_value(PARAM_BOOL, 'Has changed', VALUE_OPTIONAL),
                    'deleted' => new external_value(PARAM_BOOL, 'Is the grid deleted', VALUE_OPTIONAL),
                ]
            )),
        ]);
    }

    /**
     * Update the plannings
     *
     * @param array $plannings - The plannings to update
     * @return array
     */
    public static function update($plannings): array {
        $params = self::validate_parameters(self::update_parameters(), ['plannings' => $plannings]);
        self::validate_context(context_system::instance());
        $plannings = $params['plannings'];
        $result = true;

        // Loop through the plannings, if a planning has the haschanged flag set to true,
        // update or insert the planning by calling the correct API.
        foreach ($plannings as $planning) {
            if ($planning['deleted']) {
                plannings::delete_planning($planning['id']);
                continue;
            }
            if ($planning['haschanged']) {
                plannings::update_planning(
                    $planning['id'],
                    $planning['situationid'],
                    $planning['groupid'],
                    $planning['startdate'],
                    $planning['enddate'],
                    $planning['session']
                );
            }
        }

        return [
            'result' => $result,
        ];
    }

    /**
     * Returns description of method return value
     *
     * @return external_description
     */
    public static function update_returns(): external_single_structure {
        return new external_single_structure([
            'result' => new external_value(PARAM_BOOL, 'The processing result'),
        ]);
    }

}
