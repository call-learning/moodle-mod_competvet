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
use mod_competvet\local\persistent\cert_decl;
use mod_competvet\local\persistent\planning;
use stdClass;
use mod_competvet\local\api\certifications;

/**
 * Class add_certification_supervisor_invite Creates a new certification supervisor invite
 * only needs the declid and the supervisorid
 *
 * @package    mod_competvet
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class add_certification_invite extends external_api {

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'declid' => new external_value(PARAM_INT, 'The certification declaration id', VALUE_REQUIRED),
            'supervisorid' => new external_value(PARAM_INT, 'The supervisor id', VALUE_REQUIRED),
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
     * Add a certification supervisor invite
     *
     * @param int $declid The certification declaration id
     * @param int $supervisorid The supervisor id
     * @return stdClass
     */
    public static function execute($declid, $supervisorid): stdClass {
        global $USER;
        self::validate_parameters(self::execute_parameters(), ['declid' => $declid, 'supervisorid' => $supervisorid]);
        // Validate context : important as it also require the user to be logged in.
        $decl = cert_decl::get_record(['id' => $declid]);
        $planning  = planning::get_record(['id' => $decl->get('planningid')]);
        // Check if we can act.
        $competvet = competvet::get_from_situation_id($planning->get('situationid'));
        self::validate_context($competvet->get_context());

        certifications::declaration_supervisor_invite($declid, $supervisorid, $USER->id);
        return (object) ['success' => true];
    }
}
