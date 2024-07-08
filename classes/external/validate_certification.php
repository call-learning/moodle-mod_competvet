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
use mod_competvet\local\api\certifications;
use mod_competvet\local\persistent\cert_decl;
use mod_competvet\local\persistent\planning;

/**
 * Class validate_certification
 *
 * @package    mod_competvet
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class validate_certification extends external_api {
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
     * Validate a certification
     *
     * @param int $validid The validation id
     * @param int $declid The certification declaration id
     * @param int $level The level
     * @param string $comment The comment
     * @param int $commentformat The comment format
     * @param int $status The status
     * @return array
     */
    public static function execute($validid, $declid, $level, $comment, $commentformat, $status): array {
        ['validid' => $validid, 'declid' => $declid, 'level' => $level, 'comment' => $comment, 'commentformat' => $commentformat,
            'status' => $status] =
            self::validate_parameters(
                self::execute_parameters(),
                ['validid' => $validid,
                    'declid' => $declid, 'level' => $level, 'comment' => $comment, 'commentformat' => $commentformat,
                    'status' => $status]
            );
        $decl = cert_decl::get_record(['id' => $declid]);
        $planning = planning::get_record(['id' => $decl->get('planningid')]);
        // Check if we can delete.
        $competvet = competvet::get_from_situation_id($planning->get('situationid'));
        self::validate_context($competvet->get_context());

        if ($validid) {
            if (certifications::update_validation($validid, $status, $comment, $commentformat)) {
                return ['success' => true];
            }
        } else {
            if (certifications::validate_cert_declaration($validid, $declid, $level, $comment, $commentformat)) {
                return ['success' => true];
            }
        }
        return ['success' => false];
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'validid' => new external_value(PARAM_INT, 'The validation id', VALUE_OPTIONAL),
            'declid' => new external_value(PARAM_INT, 'The certification declaration id', VALUE_REQUIRED),
            'level' => new external_value(PARAM_INT, 'The level', VALUE_REQUIRED),
            'comment' => new external_value(PARAM_TEXT, 'The comment', VALUE_REQUIRED),
            'commentformat' => new external_value(PARAM_INT, 'The comment format', VALUE_REQUIRED),
            'status' => new external_value(PARAM_INT, 'The status', VALUE_REQUIRED),
        ]);
    }
}
