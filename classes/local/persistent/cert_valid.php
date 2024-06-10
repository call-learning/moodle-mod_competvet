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
 * Class cert_valid
 *
 * @package    mod_competvet
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cert_valid extends persistent {
    /**
     * Current table
     */
    const TABLE = 'competvet_cert_valid';

    const STATUS_CONFIRMED = 1;
    const STATUS_OBSERVER_NOTSEEN = 2;
    const STATUS_LEVEL_NOT_REACHED = 3;

    /**
     * Decl status types
     */
    const STATUS_TYPES = [
        self::STATUS_CONFIRMED => 'certvalid:confirmed',
        self::STATUS_OBSERVER_NOTSEEN => 'certvalid:notseen',
        self::STATUS_LEVEL_NOT_REACHED => 'certvalid:notreached',
    ];

    /**
     * Return the custom definition of the properties of this model.
     *
     * Each property MUST be listed here.
     *
     * @return array Where keys are the property names.
     */
    protected static function define_properties() {
        return [
            'declid' => [
                'null' => NULL_NOT_ALLOWED,
                'type' => PARAM_INT,
                'message' => new lang_string('invaliddata', 'competvet', 'declid'),
            ],
            'supervisorid' => [
                'null' => NULL_NOT_ALLOWED,
                'type' => PARAM_INT,
                'message' => new lang_string('invaliddata', 'competvet', 'supervisorid'),
            ],
            'status' => [
                'null' => NULL_NOT_ALLOWED,
                'type' => PARAM_INT,
                'message' => new lang_string('invaliddata', 'competvet', 'status'),
                'choices' => array_keys(self::STATUS_TYPES),
            ],
            'comment' => [
                'null' => NULL_ALLOWED,
                'type' => PARAM_TEXT,
                'message' => new lang_string('invaliddata', 'competvet', 'comment'),
            ],
            'commentformat' => [
                'null' => NULL_ALLOWED,
                'type' => PARAM_INT,
                'message' => new lang_string('invaliddata', 'competvet', 'commentformat'),
            ],
        ];
    }
}
