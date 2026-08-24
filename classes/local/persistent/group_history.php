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
 * Group history persistent class
 *
 * Stores historical metadata for plannings whose Moodle group has been deleted.
 *
 * @package   mod_competvet
 * @copyright 2026 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class group_history extends persistent {
    /**
     * Current table
     */
    const TABLE = 'competvet_group_history';

    /**
     * Return the custom definition of the properties of this model.
     *
     * Each property MUST be listed here.
     *
     * @return array Where keys are the property names.
     */
    protected static function define_properties() {
        return [
            'planningid' => [
                'null' => NULL_NOT_ALLOWED,
                'type' => PARAM_INT,
                'message' => new lang_string('invaliddata', 'competvet', 'planningid'),
            ],
            'groupname' => [
                'null' => NULL_NOT_ALLOWED,
                'type' => PARAM_TEXT,
                'message' => new lang_string('invaliddata', 'competvet', 'groupname'),
            ],
            'timecreated' => [
                'null' => NULL_NOT_ALLOWED,
                'type' => PARAM_INT,
                'message' => new lang_string('invaliddata', 'competvet', 'timecreated'),
            ],
            'timemodified' => [
                'null' => NULL_NOT_ALLOWED,
                'type' => PARAM_INT,
                'message' => new lang_string('invaliddata', 'competvet', 'timemodified'),
            ],
        ];
    }

    /**
     * Get a history record for a planning (uniquely indexed on planningid).
     *
     * @param int $planningid The planning ID.
     * @return group_history|null The history record, or null if not found.
     */
    public static function get_for_planning(int $planningid): ?group_history {
        $records = self::get_records(['planningid' => $planningid]);
        return reset($records) ?: null;
    }

    /**
     * Get the historical group name for a planning, or null if no history exists.
     *
     * @param int $planningid The planning ID.
     * @return string|null The preserved group name, or null.
     */
    public static function get_group_name_for_planning(int $planningid): ?string {
        $history = self::get_for_planning($planningid);
        if (!$history) {
            return null;
        }
        return $history->get('groupname');
    }

    /**
     * Check if a planning has any group history records.
     *
     * @param int $planningid The planning ID.
     * @return bool True if history exists.
     */
    public static function has_history_for_planning(int $planningid): bool {
        return self::count_records(['planningid' => $planningid]) > 0;
    }
}
