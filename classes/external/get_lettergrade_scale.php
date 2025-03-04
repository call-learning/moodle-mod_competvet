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
defined('MOODLE_INTERNAL') || die;
global $CFG;
require_once("$CFG->libdir/externallib.php");
require_once("$CFG->libdir/gradelib.php");

use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use mod_competvet\competvet;
use mod_competvet\local\api\grades;
use mod_competvet\local\persistent\planning;

/**
 * Class get_lettergrade_scale
 *
 * @package    mod_competvet
 * @copyright  2025 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_lettergrade_scale extends external_api {
    /**
     * Returns description of method return value
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'scale' => new external_value(PARAM_RAW, 'The letter scale', VALUE_REQUIRED),
        ]);
    }

    /**
     * Execute and get a letter grade scale
     * @param int $cmid - Planning instance id
     * @return array
     */
    public static function execute(int $cmid): array {
        $competvet = competvet::get_from_cmid($cmid);
        $scale = json_encode($competvet->get_lettergrade_scale(), true);
        return ['scale' => $scale];
    }

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'The planning id', VALUE_REQUIRED),
        ]);
    }
}
