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
use external_function_parameters;
use external_single_structure;
use external_value;
use mod_competvet\competvet;
use mod_competvet\local\api\grades;
use mod_competvet\local\persistent\planning;
use mod_competvet\local\persistent\grade;

/**
 * Class get_subgrades
 *
 * @package    mod_competvet
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_subgrades extends external_api {
        /**
     * Returns description of method return value
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'EVALUATION_GRADE' => new external_value(PARAM_FLOAT, 'The suggested grade', VALUE_OPTIONAL),
            'CERTIFICATION_GRADE' => new external_value(PARAM_FLOAT, 'The suggested grade', VALUE_OPTIONAL),
            'LIST_GRADE' => new external_value(PARAM_FLOAT, 'The suggested grade', VALUE_OPTIONAL),
        ]);
    }

    /**
     * Execute and get a suggested grade
     *
     * @param int $planningid - Planning instance id
     * @param int $studentid - Student id
     * @return array
     */
    public static function execute(int $planningid, int $studentid): array {
        ['planningid' => $planningid, 'studentid' => $studentid] =
            self::validate_parameters(self::execute_parameters(), ['planningid' => $planningid, 'studentid' => $studentid]);
        $planning = planning::get_record(['id' => $planningid]);
        if (!$planning) {
            throw new \moodle_exception('planningnotfound', 'mod_competvet', '', $planningid);
        }
        $competvet = competvet::get_from_situation_id($planning->get('situationid'));
        self::validate_context($competvet->get_context());

        $allgrades = grades::get_all_grades($studentid, $planningid);
        $subgrades = [];
        foreach ($allgrades as $grade) {
            $gradename = grade::DEFAULT_GRADE_SHORTNAME[$grade->get('type')];
            $subgrades[$gradename] = $grade->get('grade');
        }
        return $subgrades;
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'planningid' => new external_value(PARAM_INT, 'Planning instance id', VALUE_REQUIRED),
            'studentid' => new external_value(PARAM_INT, 'Student id', VALUE_REQUIRED),
        ]);
    }
}
