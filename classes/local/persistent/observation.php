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
 * Observation entity
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observation extends persistent {
    /**
     * Current table
     */
    const TABLE = 'competvet_observation';

    /**
     * Usual properties definition for a persistent
     *
     * @return array|array[]
     */
    protected static function define_properties() {
        return [
            'studentid' => [
                'type' => PARAM_INT,
                'null' => NULL_NOT_ALLOWED,
                'message' => new lang_string('invaliddata', 'competvet', 'studentid'),
            ],
            'observerid' => [
                'type' => PARAM_INT,
                'null' => NULL_NOT_ALLOWED,
                'message' => new lang_string('invaliddata', 'competvet', 'appraiserid'),
            ],
            'planningid' => [
                'type' => PARAM_INT,
                'null' => NULL_NOT_ALLOWED,
                'message' => new lang_string('invaliddata', 'competvet', 'evalplanid'),
            ],
            'status' => [
                'type' => PARAM_INT,
                'default' => 0,
                'message' => new lang_string('invaliddata', 'competvet', 'status'),
                'choices' => array_keys(self::STATUS),
            ],
        ];
    }

    /**
     * Status definition
     */
    const STATUS = [
        0 => 'notstarted',
        1 => 'inprogress',
        2 => 'completed',
        3 => 'archived',
    ];

    /**
     * Status not started: student asked for evaluation but not yet taken into account by observer.
     */
    const STATUS_NOTSTARTED = 0;
    /**
     * Status in progress: student is being evaluated by observer.
     */
    const STATUS_INPROGRESS = 1;
    /**
     * Status completed: student has been evaluated by observer.
     */
    const STATUS_COMPLETED = 2;
    /**
     * Status archived: student has been evaluated by observer and archived, so not counted in the stats.
     */
    const STATUS_ARCHIVED = 3;
}
