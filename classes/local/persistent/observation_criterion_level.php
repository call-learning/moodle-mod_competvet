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
 * Observation level for a given Criterion
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observation_criterion_level extends persistent {
    /**
     * Current table
     */
    const TABLE = 'competvet_obs_crit_level';

    /**
     * Return the custom definition of the properties of this model.
     *
     * Each property MUST be listed here.
     *
     * @return array Where keys are the property names.
     */
    protected static function define_properties() {
        return [
            'criterionid' => [
                'null' => NULL_NOT_ALLOWED,
                'type' => PARAM_INT,
                'message' => new lang_string('invaliddata', 'competvet', 'criterionid'),
            ],
            'observationid' => [
                'null' => NULL_NOT_ALLOWED,
                'type' => PARAM_INT,
                'message' => new lang_string('invaliddata', 'competvet', 'observationid'),
            ],
            'level' => [
                'null' => NULL_ALLOWED,
                'type' => PARAM_INT,
                'message' => new lang_string('invaliddata', 'competvet', 'level'),
                'default' => null,
            ],
            'isactive' => [
                'null' => NULL_NOT_ALLOWED,
                'type' => PARAM_BOOL,
                'default' => false,
                'message' => new lang_string('invaliddata', 'competvet', 'selected'),
            ],
        ];
    }

    /**
     * Considered as no grade (we did not touch the level / grade)
     */
    const NO_GRADE_LEVEL = 50;

    /**
     * Is this level / grade considered as empty.
     *
     * @param mixed $grade
     * @return bool
     */
    public static function is_an_empty_level($grade) {
        return is_null($grade) || $grade == self::NO_GRADE_LEVEL;
    }
}

