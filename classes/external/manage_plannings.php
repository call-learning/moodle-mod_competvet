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
                    'pauses' => new external_multiple_structure(
                        new external_single_structure([
                            'id' => new external_value(PARAM_INT, 'Pause Id', VALUE_REQUIRED),
                            'planningid' => new external_value(PARAM_INT, 'Planning Id', VALUE_REQUIRED),
                            'startdate' => new external_value(PARAM_TEXT, 'Pause start date', VALUE_REQUIRED),
                            'enddate' => new external_value(PARAM_TEXT, 'Pause end date', VALUE_REQUIRED),
                            'haschanged' => new external_value(PARAM_BOOL, 'Has changed', VALUE_OPTIONAL),
                            'deleted' => new external_value(PARAM_BOOL, 'Is the pause deleted', VALUE_OPTIONAL),
                        ])
                    ),
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
            if (isset($planning['deleted']) && $planning['deleted']) {
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

            // Handle pauses
            foreach ($planning['pauses'] as $pause) {
                if (isset($pause['deleted']) && $pause['deleted']) {
                    plannings::delete_pause($pause['id']);
                    continue;
                }
                if ($pause['haschanged']) {
                    plannings::update_pause(
                        $pause['id'],
                        $pause['planningid'],
                        $pause['startdate'],
                        $pause['enddate']
                    );
                }
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
