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
 * Class Certificate Declaration persistent class
 *
 * @package    mod_competvet
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cert_decl extends persistent {
    /**
     * Current table
     */
    const TABLE = 'competvet_cert_decl';

    const STATUS_DECL_SEENDONE = 1;
    const STATUS_DECL_NOTSEEN = 2;

    /**
     * Decl status types
     */
    const STATUS_TYPES = [
        self::STATUS_DECL_SEENDONE => 'cert:seendone',
        self::STATUS_DECL_NOTSEEN => 'cert:notseen',
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
            'criterionid' => [
                'null' => NULL_NOT_ALLOWED,
                'type' => PARAM_INT,
                'message' => new lang_string('invaliddata', 'competvet', 'criterionid'),
            ],
            'planningid' => [
                'null' => NULL_NOT_ALLOWED,
                'type' => PARAM_INT,
                'message' => new lang_string('invaliddata', 'competvet', 'planningid'),
            ],
            'studentid' => [
                'null' => NULL_NOT_ALLOWED,
                'type' => PARAM_INT,
                'message' => new lang_string('invaliddata', 'competvet', 'studentid'),
            ],
            'level' => [
                'null' => NULL_NOT_ALLOWED,
                'type' => PARAM_INT,
                'message' => new lang_string('invaliddata', 'competvet', 'level'),
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
