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
 * Grid entity
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class grid extends persistent {

    /**
     * TABLE
     */
    const TABLE = 'competvet_grid';

    /**
     * DEFAULT GRID SHORTNAME
     */
    const DEFAULT_GRID_SHORTNAME = [
        self::COMPETVET_CRITERIA_EVALUATION => 'DEFAULTEVALGRID',
        self::COMPETVET_CRITERIA_CERTIFICATION => 'DEFAULTCERTIFGRID',
        self::COMPETVET_CRITERIA_LIST => 'DEFAULTLISTGRID',
    ];

    const COMPETVET_CRITERIA_EVALUATION = 1;
    const COMPETVET_CRITERIA_CERTIFICATION = 2;
    const COMPETVET_CRITERIA_LIST = 3;

    /**
     * Grid types for competvet
     * Note that the short name 'eval', 'certif' and 'list' are used in the database
     * as a prefixof the grid field in the situation table.
     */
    const COMPETVET_GRID_TYPES = [
        self::COMPETVET_CRITERIA_EVALUATION => 'eval',
        self::COMPETVET_CRITERIA_CERTIFICATION => 'certif',
        self::COMPETVET_CRITERIA_LIST => 'list',
    ];

    /**
     * Get default grid and create it if it does not exist.
     *
     * @param int $type
     * @return grid|null
     */
    public static function get_default_grid(int $type): ?grid {
        $evalgrid = self::get_record(['idnumber' => self::DEFAULT_GRID_SHORTNAME[$type], 'type' => $type]);
        return $evalgrid ?: null;
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
            'name' => [
                'null' => NULL_NOT_ALLOWED,
                'type' => PARAM_TEXT,
                'message' => new lang_string('invaliddata', 'competvet', 'name'),
            ],
            'idnumber' => [
                'null' => NULL_NOT_ALLOWED,
                'type' => PARAM_TEXT,
                'message' => new lang_string('invaliddata', 'competvet', 'idnumber'),
            ],
            'sortorder' => [
                'null' => NULL_ALLOWED,
                'type' => PARAM_INT,
                'default' => 0,
            ],
            'type' => [
                'type' => PARAM_INT,
                'default' => 0,
                'message' => new lang_string('invaliddata', 'competvet', 'type'),
                'choices' => [
                    self::COMPETVET_CRITERIA_EVALUATION,
                    self::COMPETVET_CRITERIA_CERTIFICATION,
                    self::COMPETVET_CRITERIA_LIST,
                ],
            ],
        ];
    }

    /**
     * Hook to execute before a create operation.
     *
     * Throws an exception if the grid already exists (by idnumber).
     *
     * @return void
     */
    protected function before_create() {
        if (self::get_record(['idnumber' => $this->get('idnumber')])) {
            throw new \moodle_exception('gridalreadyexists', 'mod_competvet', '', $this->get('idnumber'));
        }
    }
}

