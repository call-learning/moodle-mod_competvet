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
use external_description;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;
use external_warnings;
use mod_competvet\external\get_json;
use mod_competvet\local\api\formdata;

/**
 * Class formdata_handler
 * Webservice class for simple storage of form data in JSON format
 *
 * @package    mod_competvet
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class formdata_handler extends external_api {

    /**
     * Returns description of method parameters. This will be used to validate the JSON data sent to the external function.
     *
     * @return external_function_parameters
     */
    public static function store_parameters(): external_function_parameters {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'The user id', VALUE_REQUIRED),
            'planningid' => new external_value(PARAM_INT, 'The planning id', VALUE_REQUIRED),
            'formname' => new external_value(PARAM_TEXT, 'The form name', VALUE_REQUIRED),
            'json' => new external_value(PARAM_TEXT, 'The JSON data', VALUE_OPTIONAL),
        ]);
    }

    /**
     * The store method to update or insert
     *
     * @param int $userid - The user id
     * @param int $planningid - The planning id
     * @param string $formname - The form name
     * @param string $json - The JSON data
     * @return array
     */
    public static function store($userid, $planningid, $formname, $json = ''): array {
        global $USER;
        $params = self::validate_parameters(self::store_parameters(), [
            'userid' => $userid,
            'planningid' => $planningid,
            'formname' => $formname,
            'json' => $json,
        ]);

        $userid = $params['userid'];
        $planningid = $params['planningid'];
        $graderid = $USER->id;
        $formname = $params['formname'];
        $json = $params['json'];

        $result = formdata::store($userid, $planningid, $graderid, $formname, $json);

        return [
            'result' => $result,
        ];
    }

    /**
     * Returns description of method return value
     *
     * @return external_single_structure
     */
    public static function store_returns(): external_single_structure {
        return new external_single_structure([
            'result' => new external_value(PARAM_BOOL, 'The processing result'),
        ]);
    }

    /**
     * Returns description of method parameters. This will be used to validate the JSON data sent to the external function.
     */
    public static function get_parameters(): external_function_parameters {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'The user id', VALUE_REQUIRED),
            'planningid' => new external_value(PARAM_INT, 'The planning id', VALUE_REQUIRED),
            'formname' => new external_value(PARAM_TEXT, 'The form name', VALUE_REQUIRED),
        ]);
    }

    /**
     * The get method to retrieve form data
     *
     * @param int $userid - The user id
     * @param int $planningid - The planning id
     * @param string $formname - The form name
     * @return array
     */
    public static function get($userid, $planningid, $formname): array {
        $params = self::validate_parameters(self::get_parameters(), [
            'userid' => $userid,
            'planningid' => $planningid,
            'formname' => $formname,
        ]);

        $userid = $params['userid'];
        $planningid = $params['planningid'];
        $formname = $params['formname'];

        $userdata = formdata::get($userid, $planningid, $formname);
        $returndata = $userdata['json'];
        $timemodified = $userdata['timemodified'];
        $result = true;

        if (!$userdata['success']) {
            $defaultdata = get_json::execute($formname); // TODO, find a better way to get default data.
            if ($defaultdata['data']) {
                $returndata = $defaultdata['data'];
                $result = true;
            } else {
                $result = false;
                $returndata = '{}';
            }
        }

        return [
            'result' => $result,
            'data' => $returndata,
            'timemodified' => $timemodified,
        ];
    }

    /**
     * Returns description of method return value
     *
     * @return external_single_structure
     */
    public static function get_returns(): external_single_structure {
        return new external_single_structure([
            'result' => new external_value(PARAM_BOOL, 'The processing result'),
            'data' => new external_value(PARAM_TEXT, 'The JSON data'),
            'timemodified' => new external_value(PARAM_INT, 'The time created'),
        ]);
    }
}
