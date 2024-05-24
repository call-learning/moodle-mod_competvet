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
use external_description;
use external_function_parameters;
use external_multiple_structure;
use external_value;
use external_single_structure;
use external_warnings;
use mod_competvet\competvet;
use stdClass;

require_once($CFG->dirroot . '/lib/gradelib.php');

/**
 * Class manage_grade
 *
 * @package    mod_competvet
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manage_grade extends external_api {
    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function update_parameters(): external_function_parameters {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'The user id', VALUE_REQUIRED),
            'cmid' => new external_value(PARAM_INT, 'The course module id', VALUE_REQUIRED),
            'grade' => new external_value(PARAM_TEXT, 'The grade', VALUE_REQUIRED),
        ]);
    }

    /**
     * Update the grade
     *
     * @param int $userid
     * @param int $cmid
     * @param string $grade
     * @return array
     */
    public static function update($userid, $cmid, $grade): array {
        $params = self::validate_parameters(self::update_parameters(), [
            'userid' => $userid,
            'cmid' => $cmid,
            'grade' => $grade,
        ]);
        // Set the grade for this user in the Moodle gradebook
        $grade = intval($params['grade']);
        $cmid = $params['cmid'];
        $userid = $params['userid'];
        $competvet = competvet::get_from_cmid($cmid);

        $grades = [];
        $grades[$userid] = [
            'userid' => $userid,
            'rawgrade' => $grade,
            'dategraded' => time(),
            'datesubmitted' => time(),
        ];

        $result = grade_update(
            'mod/competvet',
            $competvet->get_course_id(),
            'mod',
            'competvet',
            $competvet->get_instance_id(),
            0,
            $grades
        );
        if ($result == GRADE_UPDATE_OK) {
            return [
                'result' => true,
                'warnings' => [],
            ];
        } else {
            return [
                'result' => false,
                'warnings' => [],
            ];
        }
    }

    /**
     * Returns description of method return value
     *
     * @return external_description
     */
    public static function update_returns(): external_single_structure {
        return new external_single_structure([
            'result' => new external_value(PARAM_BOOL, 'The processing result'),
            'warnings' => new external_warnings(),
        ]);
    }

    /**
     * Returns description of method result value
     *
     * @return external_function_parameters
     */
    public static function get_parameters(): external_function_parameters {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'The user id', VALUE_REQUIRED),
            'cmid' => new external_value(PARAM_INT, 'The course module id', VALUE_REQUIRED),
        ]);
    }

    /**
     * Get the grade
     *
     * @param int $userid
     * @param int $cmid
     * @return array
     */
    public static function get($userid, $cmid): array {
        global $DB;
        $params = self::validate_parameters(self::get_parameters(), [
            'userid' => $userid,
            'cmid' => $cmid,
        ]);
        $cmid = $params['cmid'];
        $userid = $params['userid'];
        $competvet = competvet::get_from_cmid($cmid);
        self::validate_context($competvet->get_context());
        $grades = grade_get_grades($competvet->get_course_id(), 'mod', 'competvet', $competvet->get_instance_id(), $userid);
        $usergrade = intval($grades->items[0]->grades[$userid]->grade);

        $grade = new stdClass();
        $grade->suggestedgrade = '';
        $grade->comment = 'test';
        // If this is to be used, use the configured grade scale from moodle core.
        $grade->finalgradeoptions = [
            ['key' => 90, 'value' => 'A', 'selected' => false],
            ['key' => 80, 'value' => 'B', 'selected' => false],
            ['key' => 70, 'value' => 'C', 'selected' => false],
            ['key' => 60, 'value' => 'D', 'selected' => false],
            ['key' => 50, 'value' => 'E', 'selected' => false],
            ['key' => 40, 'value' => 'F', 'selected' => false],
            ['key' => 30, 'value' => 'G', 'selected' => false],
            ['key' => 20, 'value' => 'H', 'selected' => false],
            ['key' => 10, 'value' => 'I', 'selected' => false],
            ['key' => 0, 'value' => 'J', 'selected' => false],
        ];

        $grade->selectedgrade = 'D';
        $grade->finalgrade = $usergrade;

        if ($usergrade) {
            foreach ($grade->finalgradeoptions as &$option) {
                $option['selected'] = $option['key'] === $usergrade;
            }
        }

        return [
            'result' => $grade,
            'warnings' => [],
        ];

    }

    /**
     * Returns description of method return value
     *
     * @return external_description
     */
    public static function get_returns(): external_single_structure {
        return new external_single_structure([
            'result' => new external_single_structure(
                [
                    'suggestedgrade' => new external_value(PARAM_TEXT, 'The suggested grade'),
                    'comment' => new external_value(PARAM_TEXT, 'The comment'),
                    'finalgradeoptions' => new external_multiple_structure(
                        new external_single_structure(
                            [
                                'key' => new external_value(PARAM_TEXT, 'The key'),
                                'value' => new external_value(PARAM_TEXT, 'The value'),
                                'selected' => new external_value(PARAM_BOOL, 'The selected value'),
                            ]
                        )
                    ),
                    'selectedgrade' => new external_value(PARAM_TEXT, 'The selected grade'),
                    'finalgrade' => new external_value(PARAM_INT, 'The final grade'),
                ]
            ),
            'warnings' => new external_warnings(),
        ]);
    }
}
