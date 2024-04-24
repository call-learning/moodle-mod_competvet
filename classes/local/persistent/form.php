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

/**
 * Class form
 *
 * @package    mod_competvet
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class form extends persistent {

    /**
     * The table this class is going to interact with
     *
     * @var string
     */
    const TABLE = 'competvet_formdata';

    /**
     * Available forms
     */
    const FORMS = [
        'evaluations-grading',
        'certification-grading',
        'list-grading',
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
            'userid' => [
                'null' => NULL_NOT_ALLOWED,
                'type' => PARAM_INT,
            ],
            'planningid' => [
                'null' => NULL_NOT_ALLOWED,
                'type' => PARAM_INT,
            ],
            'graderid' => [
                'null' => NULL_NOT_ALLOWED,
                'type' => PARAM_INT,
            ],
            'formname' => [
                'null' => NULL_NOT_ALLOWED,
                'type' => PARAM_ALPHAEXT,
            ],
            'json' => [
                'null' => NULL_ALLOWED,
                'type' => PARAM_RAW,
            ],
        ];
    }
}
