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
 * Evaluation planning entity
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class planning extends persistent {
    /**
     * Current table
     */
    const TABLE = 'competvet_planning';

    /**
     * Get by date and situation
     *
     * @param int $startdate
     * @param int $enddate
     * @param int $situationid
     * @return planning
     */
    public static function get_by_dates_and_situation(int $startdate, int $enddate, int $situationid): planning {
        $params = [
            'situationid' => $situationid,
            'startdate' => $startdate,
            'enddate' => $enddate,
        ];
        return self::get_record($params);
    }

    /**
     * Return the custom definition of the properties of this model.
     *
     * Each property MUST be listed here.
     *
     * @return array Where keys are the property names.
     */
    protected static function define_properties() {
        return [
            'situationid' => [
                'null' => NULL_NOT_ALLOWED,
                'type' => PARAM_INT,
                'message' => new lang_string('invaliddata', 'competvet', 'situationid'),
            ],
            'groupid' => [
                'null' => NULL_NOT_ALLOWED,
                'type' => PARAM_INT,
                'message' => new lang_string('invaliddata', 'competvet', 'groupid'),
            ],
            'startdate' => [
                'null' => NULL_NOT_ALLOWED,
                'type' => PARAM_INT,
                'message' => new lang_string('invaliddata', 'competvet', 'startdate'),
            ],
            'enddate' => [
                'null' => NULL_NOT_ALLOWED,
                'type' => PARAM_INT,
                'message' => new lang_string('invaliddata', 'competvet', 'enddate'),
            ],
            'session' => [
                'type' => PARAM_ALPHANUMEXT,
                'default' => get_config('mod_competvet', 'defaultsession'),
                'formtype' => 'text',
                'formoptions' => ['size' => '64'],
            ],
        ];
    }

    /**
     * Get printable version of start time
     *
     * @return string
     */
    public function get_startdate_string() {
        return userdate($this->raw_get('startdate'), get_string('strftimedate', 'core_langconfig'));
    }

    /**
     * Get printable version of end time
     *
     * @return string
     */
    public function get_enddate_string() {
        return userdate($this->raw_get('enddate'), get_string('strftimedate', 'core_langconfig'));
    }
}
