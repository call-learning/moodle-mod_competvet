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

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/externallib.php");

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;
use mod_competvet\local\api\todos as todos_api;
use mod_competvet\local\persistent\todo;

/**
 * Class get_todos
 *
 * @package    mod_competvet
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_todos extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'User ID', VALUE_REQUIRED),
        ]);
    }

    /**
     * Get todos
     * @param int $userid
     * @return array
     */
    public static function execute(int $userid): array {
        $todos = todos_api::get_todos_for_user($userid);
        foreach ($todos as &$todo) {
            $statusoptions = todo::STATUS;
            $status = $statusoptions[$todo['status']];
            $todo['status'] = get_string('todo:status:' . $status, 'mod_competvet');
            $todo['evalasked'] = $todo['action'] == todo::ACTION_EVAL_OBSERVATION_ASKED;
            $todo['certifvalidasked'] = $todo['action'] == todo::ACTION_EVAL_CERTIFICATION_VALIDATION_ASKED;
            $todo['declid'] = null;
            $todo['observationid'] = null;
            $data = json_decode($todo['data']);
            if ($data && isset($data->declid)) {
                $todo['declid'] = $data->declid;
            }
        }
        return ['todos' => $todos];
    }

    /**
     * Returns description of method result value
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'todos' => new external_multiple_structure(new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Todo ID', VALUE_REQUIRED),
                'planning' => new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Planning ID', VALUE_REQUIRED),
                    'situationname' => new external_value(PARAM_TEXT, 'Situation name', VALUE_REQUIRED),
                    'groupname' => new external_value(PARAM_TEXT, 'Group name', VALUE_REQUIRED),
                    'startdate' => new external_value(PARAM_INT, 'Start date', VALUE_REQUIRED),
                    'enddate' => new external_value(PARAM_INT, 'End date', VALUE_REQUIRED),
                    'cmid' => new external_value(PARAM_INT, 'Course module ID', VALUE_REQUIRED),
                    'session' => new external_value(PARAM_TEXT, 'Session', VALUE_REQUIRED),
                ]),
                'timecreated' => new external_value(PARAM_INT, 'timecreated', VALUE_REQUIRED),
                'timemodified' => new external_value(PARAM_INT, 'timemodified', VALUE_REQUIRED),
                'targetuser' => new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'User ID', VALUE_REQUIRED),
                    'fullname' => new external_value(PARAM_TEXT, 'Fullname', VALUE_REQUIRED),
                    'userpictureurl' => new external_value(PARAM_TEXT, 'User picture URL', VALUE_REQUIRED),
                ]),
                'action' => new external_value(PARAM_TEXT, 'Action', VALUE_REQUIRED),
                'evalasked' => new external_value(PARAM_BOOL, 'Eval asked', VALUE_REQUIRED),
                'certifvalidasked' => new external_value(PARAM_BOOL, 'Certif valid asked', VALUE_REQUIRED),
                'status' => new external_value(PARAM_TEXT, 'Status', VALUE_REQUIRED),
                'data' => new external_value(PARAM_RAW, 'Data', VALUE_REQUIRED),
                'declid' => new external_value(PARAM_INT, 'Declaration ID', VALUE_REQUIRED),
            ])),
        ]);
    }
}
