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

namespace mod_competvet\local\persistent;

use core\persistent;
use lang_string;
use mod_competvet\local\api\progression;

/**
 * Materialized competency progression result.
 *
 * @package   mod_competvet
 * @copyright 2026 CALL Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class progression_result extends persistent {
    /** @var string Database table. */
    const TABLE = 'competvet_progression';

    /**
     * Define persistent properties.
     *
     * @return array
     */
    protected static function define_properties() {
        return [
            'studentid' => [
                'type' => PARAM_INT,
                'null' => NULL_NOT_ALLOWED,
                'message' => new lang_string('invaliddata', 'mod_competvet', 'studentid'),
            ],
            'situationid' => [
                'type' => PARAM_INT,
                'null' => NULL_NOT_ALLOWED,
                'message' => new lang_string('invaliddata', 'mod_competvet', 'situationid'),
            ],
            'planningid' => [
                'type' => PARAM_INT,
                'null' => NULL_NOT_ALLOWED,
                'message' => new lang_string('invaliddata', 'mod_competvet', 'planningid'),
            ],
            'criterionid' => [
                'type' => PARAM_INT,
                'null' => NULL_NOT_ALLOWED,
                'message' => new lang_string('invaliddata', 'mod_competvet', 'criterionid'),
            ],
            'status' => [
                'type' => PARAM_INT,
                'null' => NULL_NOT_ALLOWED,
                'default' => progression::STATE_NOT_EVALUATED,
                'message' => new lang_string('invaliddata', 'mod_competvet', 'status'),
            ],
            'bestlevel' => [
                'type' => PARAM_INT,
                'null' => NULL_ALLOWED,
                'default' => null,
                'message' => new lang_string('invaliddata', 'mod_competvet', 'bestlevel'),
            ],
            'totalobservations' => [
                'type' => PARAM_INT,
                'null' => NULL_NOT_ALLOWED,
                'default' => 0,
                'message' => new lang_string('invaliddata', 'mod_competvet', 'totalobservations'),
            ],
            'timecalculated' => [
                'type' => PARAM_INT,
                'null' => NULL_NOT_ALLOWED,
                'default' => 0,
                'message' => new lang_string('invaliddata', 'mod_competvet', 'timecalculated'),
            ],
        ];
    }
}
