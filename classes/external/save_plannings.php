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
use external_api;
use external_function_parameters;
use external_value;
use stdClass;
use external_single_structure;
use external_multiple_structure;
use mod_competvet\competvet;
use mod_competvet\local\api\plannings as plannings_api;

$examplejson = '{
    "categories": [
        {
            "categorytext": "Late",
            "categoryid": 10,
            "plannings": [
                {
                    "id": 1,
                    "startdate": "2024-01-10",
                    "enddate": "2024-01-18",
                    "groupname": "Nurses",
                    "nbstudents": 10
                },
                {
                    "id": 2,
                    "startdate": "2024-01-08",
                    "enddate": "2024-01-10",
                    "groupname": "Surgeons",
                    "nbstudents": 17
                }
            ]
        },
        {
            "categorytext": "Current",
            "categoryid": 0,
            "plannings": [
                {
                    "id": 4,
                    "startdate": "2024-03-19",
                    "enddate": "2024-03-29",
                    "groupname": "Nurses",
                    "nbstudents": 10
                }
            ]
        }
    ],
    "version": 1711528577,
    "uniqid": 0,
    "globals": {
        "config": {
            "wwwroot": "http://localhost/competvet",
            "homeurl": {},
            "sesskey": "MCTc6LC0FH",
            "sessiontimeout": "28800",
            "sessiontimeoutwarning": "1200",
            "themerev": "1711469079",
            "slasharguments": 1,
            "theme": "boost",
            "iconsystemmodule": "core/icon_system_fontawesome",
            "jsrev": -1,
            "admin": "admin",
            "svgicons": true,
            "usertimezone": "Europe/London",
            "language": "en",
            "courseId": 9,
            "courseContextId": 462,
            "contextid": 533,
            "contextInstanceId": 450,
            "langrev": 1711469079,
            "templaterev": "1711469079",
            "developerdebug": true
        }
    },
    "currentTheme": "boost"
}';
/**
 * Class save_plannings
 * This class saves the plannings returned by the UI when the state of the plannings is changed.
 *
 * @package    mod_competvet
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class save_plannings extends external_api {
    /**
     * Returns description of method parameters
     * Look at the example_json for the structure of the data
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'categories' => new external_multiple_structure(new external_single_structure([
                'categorytext' => new external_value(PARAM_TEXT, 'Category text', VALUE_REQUIRED),
                'categoryid' => new external_value(PARAM_INT, 'Category id', VALUE_REQUIRED),
                'plannings' => new external_multiple_structure(new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Planning id', VALUE_REQUIRED),
                    'startdate' => new external_value(PARAM_TEXT, 'Start date', VALUE_REQUIRED),
                    'enddate' => new external_value(PARAM_TEXT, 'End date', VALUE_REQUIRED),
                    'groupname' => new external_value(PARAM_TEXT, 'Group name', VALUE_REQUIRED),
                    'nbstudents' => new external_value(PARAM_INT, 'Number of students', VALUE_REQUIRED),
                ])),
            ])),
        ]);
    }

    /**
     * Execute and return plannings list
     *
     * @param array $categories - The categories list
     * @return array $data - The categories list
     * @throws \invalid_parameter_exception
     */
    public static function execute(array $categories): array {
        global $USER;
        ['categories' => $categories] = self::validate_parameters(self::execute_parameters(), ['categories' => $categories]);
        self::validate_context(context_system::instance());
        return ['categories' => $categories];
    }

    /**
     * Returns description of method result value
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'categories' => new external_multiple_structure(new external_single_structure([
                'categorytext' => new external_value(PARAM_TEXT, 'Category text', VALUE_REQUIRED),
                'categoryid' => new external_value(PARAM_INT, 'Category id', VALUE_REQUIRED),
                'plannings' => new external_multiple_structure(new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Planning id', VALUE_REQUIRED),
                    'startdate' => new external_value(PARAM_TEXT, 'Start date', VALUE_REQUIRED),
                    'enddate' => new external_value(PARAM_TEXT, 'End date', VALUE_REQUIRED),
                    'groupname' => new external_value(PARAM_TEXT, 'Group name', VALUE_REQUIRED),
                    'nbstudents' => new external_value(PARAM_INT, 'Number of students', VALUE_REQUIRED),
                ])),
            ])),
        ]);
    }
}
