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
use mod_competvet\local\persistent\planning;
use stdClass;
use mod_competvet\local\api\certifications;

/**
 * Class Webservice add_certification, calls the add_certification method from the certifications class
 *
 * @package    mod_competvet
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class add_certification extends external_api {

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'declid' => new external_value(PARAM_INT, 'The certification declaration id', VALUE_OPTIONAL),
            'criterionid' => new external_value(PARAM_INT, 'The criterion id', VALUE_REQUIRED),
            'studentid' => new external_value(PARAM_INT, 'The student id', VALUE_REQUIRED),
            'planningid' => new external_value(PARAM_INT, 'The planning id', VALUE_REQUIRED),
            'level' => new external_value(PARAM_INT, 'The level', VALUE_REQUIRED),
            'comment' => new external_value(PARAM_TEXT, 'The comment', VALUE_REQUIRED),
            'commentformat' => new external_value(PARAM_INT, 'The comment format', VALUE_REQUIRED),
            'status' => new external_value(PARAM_INT, 'The status', VALUE_REQUIRED),
        ]);
    }

    /**
     * Returns description of method return value
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'declid' => new external_value(PARAM_INT, 'The certification declaration id'),
        ]);
    }

    /**
     * Add a certification
     *
     * @param int $declid The certification declaration id
     * @param int $criterionid The criterion id
     * @param int $studentid The student id
     * @param int $planningid The planning id
     * @param int $level The level
     * @param string $comment The comment
     * @param int $commentformat The comment format
     * @param int $status The status
     * @return array
     */
    public static function execute($declid, $criterionid, $studentid, $planningid, $level, $comment, $commentformat, $status): array {
        self::validate_parameters(self::execute_parameters(), [
            'criterionid' => $criterionid,
            'studentid' => $studentid,
            'planningid' => $planningid,
            'level' => $level,
            'comment' => $comment,
            'commentformat' => $commentformat,
            'status' => $status,
        ]);
        // Validate context : important as it also require the user to be logged in.
        $planning  = planning::get_record(['id' => $planningid]);
        // Check if we can add.
        $competvet = competvet::get_from_situation_id($planning->get('situationid'));
        self::validate_context($competvet->get_context());

        if ($declid) {
            certifications::update_certification($declid, $level, $comment, $commentformat, $status);
            return ['declid' => $declid];
        }
        $certid = certifications::add_certification(
            $criterionid,
            $studentid,
            $planningid,
            $level,
            $comment,
            $commentformat,
            $status,
        );
        return ['declid' => $certid];
    }
}
