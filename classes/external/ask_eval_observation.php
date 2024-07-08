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
use external_function_parameters;
use external_value;
use mod_competvet\competvet;
use mod_competvet\local\api\todos;
use mod_competvet\local\persistent\planning;
use mod_competvet\local\persistent\situation;

/**
 * Class delete_observation
 *
 * @package   mod_cveteval
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ask_eval_observation extends external_api {
    /**
     * Returns description of method return value
     *
     * @return \external_single_structure
     */
    public static function execute_returns(): \external_single_structure {
        return new \external_single_structure(
            [
                'todoid' => new external_value(PARAM_INT, 'Observation instance id'),
            ]
        );
    }

    /**
     * Execute and return observation list
     *
     * @param int $observationid - Observation instance id
     * @return array|array[]
     * @throws \invalid_parameter_exception
     */
    public static function execute(string $context, int $planningid, int $observerid, int $studentid): array {
        [
            'context' => $context,
            'planningid' => $planningid,
            'observerid' => $observerid,
            'studentid' => $studentid,
        ] =
            self::validate_parameters(self::execute_parameters(), [
                'context' => $context,
                'planningid' => $planningid,
                'observerid' => $observerid,
                'studentid' => $studentid,
            ]);
        $planning = planning::get_record(['id' => $planningid]);
        // Check if we can act.
        $competvet = competvet::get_from_situation_id($planning->get('situationid'));
        self::validate_context($competvet->get_context());
        return [
            'todoid' => todos::ask_for_observation($context, $planningid, $observerid, $studentid),
        ];
    }


    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'context' => new external_value(PARAM_TEXT, 'Context', VALUE_REQUIRED),
            'planningid' => new external_value(PARAM_INT, 'Planning instance id', VALUE_REQUIRED),
            'observerid' => new external_value(PARAM_INT, 'Observer id', VALUE_REQUIRED),
            'studentid' => new external_value(PARAM_INT, 'Student id', VALUE_REQUIRED),
        ]);
    }
}
