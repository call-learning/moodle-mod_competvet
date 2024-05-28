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

use mod_competvet\competvet;
use mod_competvet\local\api\cases;
use mod_competvet\local\persistent\planning;
use stdClass;

/**
 * Class get_cases
 *
 * @package    mod_competvet
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_cases extends external_api {

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'The user id', VALUE_OPTIONAL),
            'planningid' => new external_value(PARAM_INT, 'The planning id', VALUE_OPTIONAL),
        ]);
    }

    /**
     * Returns description of method return value
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'cases' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'The case id'),
                    'timecreated' => new external_value(PARAM_INT, 'The time the case was created'),
                    'categories' => new external_multiple_structure(
                        new external_single_structure([
                            'id' => new external_value(PARAM_INT, 'The category id'),
                            'name' => new external_value(PARAM_TEXT, 'The category name'),
                            'fields' => new external_multiple_structure(
                                new external_single_structure([
                                    'id' => new external_value(PARAM_INT, 'The field id'),
                                    'idnumber' => new external_value(PARAM_TEXT, 'The field shortname'),
                                    'name' => new external_value(PARAM_TEXT, 'The field name'),
                                    'type' => new external_value(PARAM_TEXT, 'The field type'),
                                    'configdata' => new external_value(PARAM_RAW, 'The field configdata'),
                                    'description' => new external_value(PARAM_TEXT, 'The field description'),
                                    'value' => new external_value(PARAM_TEXT, 'The field value'),
                                    'displayvalue' => new external_value(PARAM_TEXT, 'The field display value'),
                                ])
                            ),
                        ])
                    ),
                ])
            ),
        ]);
    }

    /**
     * Execute and get the cases for a user, or an empty case structure.
     *
     * @param int $userid The user id
     * @param int $planningid The planning id
     * @return stdClass
     * @throws \invalid_parameter_exception
     */
    public static function execute(int $userid, int $planningid): stdClass {
        [
            'userid' => $userid,
            'planningid' => $planningid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'userid' => $userid,
            'planningid' => $planningid,
        ]);
        $planning  = planning::get_record(['id' => $planningid]);
        // Check if we can delete.
        $competvet = competvet::get_from_situation_id($planning->get('situationid'));
        self::validate_context($competvet->get_context());

        $cases = cases::get_entries($userid, $planningid);
        return $cases;
    }
}
