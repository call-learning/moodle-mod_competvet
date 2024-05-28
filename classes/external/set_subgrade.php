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
use mod_competvet\competvet;
use mod_competvet\local\api\grades;
use mod_competvet\local\persistent\planning;

/**
 * Class set_suggested_grade
 *
 * @package    mod_competvet
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class set_subgrade extends external_api {

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'planningid' => new external_value(PARAM_INT, 'Planning instance id', VALUE_REQUIRED),
            'studentid' => new external_value(PARAM_INT, 'Student id', VALUE_REQUIRED),
            'type' => new external_value(PARAM_INT, 'Grade type', VALUE_REQUIRED),
            'grade' => new external_value(PARAM_FLOAT, 'The grade', VALUE_REQUIRED),
        ]);
    }

    /**
     * Returns description of method return value
     *
     * @return external_description
     */
    public static function execute_returns(): ?external_description {
        return null;
    }

    /**
     * Execute and set a subgrade
     * @param int $planningid - Planning instance id
     * @param int $studentid - Student id
     * @param int $type - Grade type
     * @param int $grade - The grade
     */
    public static function execute(int $planningid, int $studentid, int $type, int $grade) {
        ['planningid' => $planningid, 'studentid' => $studentid, 'type' => $type, 'grade' => $grade] = self::validate_parameters(
            self::execute_parameters(),
            ['planningid' => $planningid, 'studentid' => $studentid, 'type' => $type, 'grade' => $grade]
        );
        $planning  = planning::get_record(['id' => $planningid]);
        // Check if we can delete.
        $competvet = competvet::get_from_situation_id($planning->get('situationid'));

        self::validate_context($competvet->get_context());

        return grades::set_grade($studentid, $planningid, $type, $grade);
    }
}
