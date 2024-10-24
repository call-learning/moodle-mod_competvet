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
// This is for 4.4 compatibility.
defined('MOODLE_INTERNAL') || die;
global $CFG;
require_once("$CFG->libdir/externallib.php");
require_once($CFG->libdir.'/gradelib.php');

use external_api;
use external_description;
use external_function_parameters;
use external_multiple_structure;
use external_value;
use external_single_structure;
use external_warnings;
use mod_competvet\competvet;
use mod_competvet\local\api\formdata;
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
            'planningid' => new external_value(PARAM_INT, 'The planning id', VALUE_REQUIRED),
            'grade' => new external_value(PARAM_TEXT, 'The grade', VALUE_REQUIRED),
            'feedback' => new external_value(PARAM_TEXT, 'The feedback', VALUE_OPTIONAL),
        ]);
    }

    /**
     * Update the grade
     *
     * @param int $userid
     * @param int $cmid
     * @param int $planningid
     * @param string $grade
     * @param string $feedback
     * @param int $planningid
     * @return array
     */
    public static function update($userid, $cmid, $planningid, $grade, $feedback): array {
        global $DB;
        $params = self::validate_parameters(self::update_parameters(), [
            'userid' => $userid,
            'cmid' => $cmid,
            'planningid' => $planningid,
            'grade' => $grade,
            'feedback' => $feedback,
        ]);
        // Set the grade for this user in the Moodle gradebook
        $grade = intval($params['grade']);
        $cmid = $params['cmid'];
        $planningid = $params['planningid'];
        $userid = $params['userid'];
        $feedback = $params['feedback'];
        $competvet = competvet::get_from_cmid($cmid);
        self::validate_context($competvet->get_context());

        if (!has_capability('mod/competvet:cangrade', $competvet->get_context())) {
            return [
                'result' => false,
                'warnings' => [[
                    'item' => $cmid,
                    'warningcode' => 'nopermission',
                    'message' => 'Capability error: you do not have permission to grade this item.',
                ], ],
            ];
        }

        $item = \grade_item::fetch([
            'itemtype' => 'mod',
            'itemmodule' => 'competvet',
            'iteminstance' => $competvet->get_instance_id(),
            'courseid' => $competvet->get_course_id(),
        ]);
        $result = $item->update_final_grade($userid, $grade, null, $feedback, FORMAT_HTML);

        $customdata = json_encode((object)[
            'studentid' => $userid,
            'cmid' => $competvet->get_course_module_id(),
            'planningid' => $planningid
        ]);
        $existing = $DB->record_exists_select('task_adhoc',
            "classname = :classname AND " . $DB->sql_compare_text('customdata') . " = " . $DB->sql_compare_text(':customdata'),
            [
                'classname' => '\mod_competvet\task\student_graded',
                'customdata' => $customdata,
            ]
        );

        if (!$existing) {
            $task = new \mod_competvet\task\student_graded();
            $task->set_custom_data((object)[
                'studentid' => $userid,
                'cmid' => $competvet->get_course_module_id(),
                'planningid' => $planningid
            ]);
            \core\task\manager::queue_adhoc_task($task);
        }

        if ($result) {
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
            'planningid' => new external_value(PARAM_INT, 'The planning id', VALUE_REQUIRED),
        ]);
    }

    /**
     * Get the grade
     *
     * @param int $userid
     * @param int $cmid
     * @param int $planningid
     * @return array
     */
    public static function get($userid, $cmid, $planningid): array {
        $params = self::validate_parameters(self::get_parameters(), [
            'userid' => $userid,
            'cmid' => $cmid,
            'planningid' => $planningid,
        ]);
        $cmid = $params['cmid'];
        $userid = $params['userid'];
        $competvet = competvet::get_from_cmid($cmid);
        self::validate_context($competvet->get_context());

        $usergrade = $competvet->get_final_grade_for_student($userid);
        $grade = new stdClass();
        $grade->suggestedgrade = '';
        $grade->finalgrade = intval($usergrade->finalgrade);
        $grade->comment = $usergrade->feedback;
        $grade->cangrade = has_capability('mod/competvet:cangrade', $competvet->get_context());

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
                    'comment' => new external_value(PARAM_TEXT, 'The comment'),
                    'suggestedgrade' => new external_value(PARAM_TEXT, 'The suggested grade'),
                    'finalgrade' => new external_value(PARAM_INT, 'The final grade'),
                    'cangrade' => new external_value(PARAM_BOOL, 'Can grade'),
                ]
            ),
            'warnings' => new external_warnings(),
        ]);
    }
}
