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
use external_single_structure;
use external_value;
use mod_competvet\competvet;
use mod_competvet\local\persistent\case_entry;
use mod_competvet\local\persistent\cert_decl;
use mod_competvet\local\persistent\planning;
use stdClass;
use mod_competvet\local\api\cases;

/**
 * Class delete_entry
 *
 * @package    mod_competvet
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class delete_entry extends external_api {
        /**
         * Returns description of method parameters
         *
         * @return external_function_parameters
         */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'entryid' => new external_value(PARAM_INT, 'The entry id', VALUE_REQUIRED),
        ]);
    }

        /**
         * Returns description of method return value
         *
         * @return external_single_structure
         */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'The success of the operation'),
        ]);
    }

        /**
         * Delete a case
         *
         * @param int $entryid The case id
         * @return stdClass
         */
    public static function execute(int $entryid): stdClass {
        self::validate_parameters(self::execute_parameters(), ['entryid' => $entryid]);
        $decl = case_entry::get_record(['id' => $entryid]);
        $planning  = planning::get_record(['id' => $decl->get('planningid')]);
        // Check if we can delete.
        $competvet = competvet::get_from_situation($planning->get('situationid'));
        self::validate_context($competvet->get_context());

        if (cases::delete_case($entryid)) {
            return (object) ['success' => true];
        }
        return (object) ['success' => false];
    }
}
