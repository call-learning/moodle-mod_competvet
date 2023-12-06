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

/**
 * Observation Context entity
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observation_context extends persistent {
    /**
     * Current table
     */
    const TABLE = 'competvet_obs_context';

    /**
     * Usual properties definition for a persistent
     *
     * @return array|array[]
     */
    protected static function define_properties() {
        return [
            'observationid' => [
                'type' => PARAM_INT,
                'null' => NULL_NOT_ALLOWED,
                'message' => new lang_string('invaliddata', 'competvet', 'observationid'),
            ],
            'usercreated' => [
                'type' => PARAM_INT,
                'null' => NULL_NOT_ALLOWED,
                'message' => new lang_string('invaliddata', 'competvet', 'usercreated'),
            ],
            'context' => [
                'type' => PARAM_TEXT,
                'default' => '',
            ],
            'contextformat' => [
                'type' => PARAM_INT,
                'default' => FORMAT_PLAIN,
            ],
        ];
    }
}
