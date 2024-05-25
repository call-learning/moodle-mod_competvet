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

use core_user;
use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;

/**
 * Class planning_info
 *
 * @package   mod_cveteval
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_planning_info extends external_api {
    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course Module Id', VALUE_REQUIRED),
            'userid' => new external_value(PARAM_INT, 'User Id', VALUE_REQUIRED),
        ]);
    }

    /**
     * Returns description of method return value
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'plannings' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'User id'),
                    'startdate' => new external_value(PARAM_INT, 'Start date'),
                    'enddate' => new external_value(PARAM_INT, 'End date'),
                    'groupid' => new external_value(PARAM_INT, 'Group ID'),

                ])
            ),
        ]);
    }

    /**
     * Execute and return plannings list
     *
     * @param int $cmid - Course Module Id
     * @param int $userid - User Id
     * @return array|array[]
     * @throws \invalid_parameter_exception
     */
    public static function execute(int $cmid, int $userid) {
        global $DB;
        $cm = get_coursemodule_from_id('competvet', $cmid, 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context(\context_module::instance($cmid));

        $user = core_user::get_user($userid);
        // Now, can this user view other users.
        course_require_view_participants($context->get_parent_context());
        $groups = groups_get_all_groups($cm->course, $userid, 0, 'g.*');
        $plannings = [];
        foreach ($groups as $g) {
            $planningentries =
                $DB->get_records(
                    'competvet_planning',
                    ['situationid' => $cm->instance, 'groupid' => $g->id],
                    'groupid, startdate, enddate ASC'
                );
            foreach ($planningentries as $planning) {
                $plannings[] = (object) [
                    'id' => $planning->id,
                    'startdate' => $planning->startdate,
                    'enddate' => $planning->enddate,
                    'groupid' => $g->id,
                ];
            }
        }
        return [
            'plannings' => $plannings,
        ];
    }
}
