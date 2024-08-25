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
 * Class grades
 *
 * @package    mod_competvet
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class grade extends persistent {
    /**
     * Current table
     */
    const TABLE = 'competvet_grades';

    const EVALUATION_GRADE = 1;
    const CERTIFICATION_GRADE = 2;
    const LIST_GRADE = 3;

    /**
     * DEFAULT GRADE SHORTNAME
     */
    const DEFAULT_GRADE_SHORTNAME = [
        self::EVALUATION_GRADE => 'EVALUATION_GRADE',
        self::CERTIFICATION_GRADE => 'CERTIFICATION_GRADE',
        self::LIST_GRADE => 'LIST_GRADE',
    ];

    /**
     * Grade types
     */
    const GRADE_TYPES = [
        self::EVALUATION_GRADE,
        self::CERTIFICATION_GRADE,
        self::LIST_GRADE,
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
            'competvet' => [
                'null' => NULL_NOT_ALLOWED,
                'type' => PARAM_INT,
                'message' => new lang_string('invaliddata', 'competvet', 'competvet'),
            ],
            'type' => [
                'null' => NULL_NOT_ALLOWED,
                'type' => PARAM_INT,
                'message' => new lang_string('invaliddata', 'competvet', 'itemnumber'),
                'choices' => [
                    self::EVALUATION_GRADE,
                    self::CERTIFICATION_GRADE,
                    self::LIST_GRADE,
                ],
            ],
            'studentid' => [
                'null' => NULL_NOT_ALLOWED,
                'type' => PARAM_INT,
                'message' => new lang_string('invaliddata', 'competvet', 'studentid'),
            ],
            'grade' => [
                'null' => NULL_ALLOWED,
                'type' => PARAM_FLOAT,
                'message' => new lang_string('invaliddata', 'competvet', 'grade'),
            ],
            'planningid' => [
                'null' => NULL_ALLOWED,
                'type' => PARAM_INT,
                'message' => new lang_string('invaliddata', 'competvet', 'planningid'),
            ],
        ];
    }
}
