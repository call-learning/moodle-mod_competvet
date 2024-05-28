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
use mod_competvet\local\persistent\case_entry;
use mod_competvet\local\persistent\observation;
use mod_competvet\local\persistent\planning;

/**
 * Class delete_observation
 *
 * @package   mod_cveteval
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class delete_observation extends external_api {
    /**
     * Returns description of method return value
     *
     * @return external_description
     */
    public static function execute_returns(): ?external_description {
        return null;
    }

    /**
     * Execute and return observation list
     *
     * @param int $observationid - Observation instance id
     * @return array|array[]
     * @throws \invalid_parameter_exception
     */
    public static function execute(int $observationid): void {
        ['observationid' => $observationid] =
            self::validate_parameters(self::execute_parameters(), ['observationid' => $observationid]);
        $observation = observation::get_record(['id' => $observationid]);
        $planning = planning::get_record(['id' => $observation->get('planningid')]);
        $competvet = competvet::get_from_situation_id($planning->get('situationid'));
        self::validate_context($competvet->get_context());



        // Check if we can delete.
        $situation = $observation->get_situation();
        $competvet = competvet::get_from_situation($situation);
        self::validate_context($competvet->get_context());
        $observation->delete();
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'observationid' => new external_value(PARAM_INT, 'Observation instance id', VALUE_REQUIRED),
        ]);
    }
}
