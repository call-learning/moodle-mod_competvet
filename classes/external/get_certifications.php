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

namespace mod_competvet\external;

use external_api;
use external_function_parameters;
use external_single_structure;
use external_multiple_structure;
use external_value;
use mod_competvet\local\api\certifications;

/**
 * External webservice class to get all certifition validations
 *
 * @package    mod_competvet
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_certifications extends external_api {

    /**
    * Returns description of method parameters
    *
    * @return external_function_parameters
    */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'studentid' => new external_value(PARAM_INT, 'The user id', VALUE_REQUIRED),
            'planningid' => new external_value(PARAM_INT, 'The planning id', VALUE_REQUIRED),
        ]);
    }

    /**
    * Returns description of method return value
    *
    */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'certifications' => new external_multiple_structure(
                new external_single_structure([
                    'declid' => new external_value(PARAM_INT, 'The certification id'),
                    'label' => new external_value(PARAM_TEXT, 'The label'),
                    'criterionid' => new external_value(PARAM_INT, 'The criterion id'),
                    'level' => new external_value(PARAM_INT, 'The level', VALUE_OPTIONAL),
                    'total' => new external_value(PARAM_INT, 'The total', VALUE_OPTIONAL),
                    'status' => new external_value(PARAM_INT, 'The status', VALUE_OPTIONAL),
                    'realised' => new external_value(PARAM_BOOL, 'Is realised', VALUE_OPTIONAL),
                    'notrealised' => new external_value(PARAM_BOOL, 'Is not realised', VALUE_OPTIONAL),
                    'validated' => new external_value(PARAM_BOOL, 'Is validated', VALUE_OPTIONAL),
                    'notvalidated' => new external_value(PARAM_BOOL, 'Is not validated', VALUE_OPTIONAL),
                    'notreached' => new external_value(PARAM_BOOL, 'Is not reached', VALUE_OPTIONAL),
                    'feedback' => new external_single_structure([
                        'picture' => new external_value(PARAM_TEXT, 'The picture'),
                        'fullname' => new external_value(PARAM_TEXT, 'The fullname'),
                        'comments' => new external_single_structure([
                            'commenttext' => new external_value(PARAM_TEXT, 'The comment'),
                        ], 'The comments', VALUE_OPTIONAL)
                    ], 'The feedback', VALUE_OPTIONAL),
                    'validations' => new external_multiple_structure(
                        new external_single_structure([
                            'id' => new external_value(PARAM_INT, 'The validation id'),
                            'feedback' => new external_single_structure([
                                'picture' => new external_value(PARAM_TEXT, 'The picture'),
                                'fullname' => new external_value(PARAM_TEXT, 'The fullname'),
                                'comments' => new external_single_structure([
                                    'commenttext' => new external_value(PARAM_TEXT, 'The comment'),
                                ])
                            ]),
                            'status' => new external_value(PARAM_INT, 'The status'),
                        ]), 'The validations', VALUE_OPTIONAL
                    ),
                ])
            ),
        ]);
    }

    /**
    * Get all certifications
    *
    * @param int $studentid The student id
    * @param int $planningid The planning id
    * @return array
    */
    public static function execute($studentid, $planningid): array {
        $certifications = certifications::get_certifications($studentid, $planningid);
        return ['certifications' => $certifications];
    }
}