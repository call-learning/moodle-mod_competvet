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

use core_reportbuilder\local\filters\number;
use external_api;
use external_description;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;
use mod_competvet\competvet;
use mod_competvet\local\api\observations;
use mod_competvet\local\persistent\planning;

/**
 * Class get_evaluations
 *
 * @package    mod_competvet
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_evaluations extends external_api {

    /**
     * Returns description of method return value
     *
     * @return external_description
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure
        (
            [
                'evaluations' => new external_multiple_structure(
                    new external_single_structure(
                        [
                            'id' => new external_value(PARAM_INT, 'Evaluation id'),
                            'name' => new external_value(PARAM_TEXT, 'Evaluation name'),
                            'grades' => new external_multiple_structure(
                                new external_single_structure(
                                    [
                                        'userid' => new external_value(PARAM_INT, 'User id'),
                                        'gradername' => new external_value(PARAM_TEXT, 'Grader name'),
                                        'value' => new external_value(PARAM_INT, 'Grade'),
                                    ]
                                )
                            ),
                        ]
                    )
                ),
                'comments' => new external_multiple_structure(
                    new external_single_structure(
                        [
                            'userid' => new external_value(PARAM_INT, 'User id'),
                            'fullname' => new external_value(PARAM_TEXT, 'Full name'),
                            'picture' => new external_value(PARAM_URL, 'User picture url'),
                            'comments' => new external_multiple_structure(
                                new external_single_structure(
                                    [
                                        'id' => new external_value(PARAM_INT, 'Comment id'),
                                        'commenttext' => new external_value(PARAM_TEXT, 'Comment'),
                                        'timecreated' => new external_value(PARAM_INT, 'Time created'),
                                    ]
                                )
                            ),
                        ]
                    )
                ),
            ]
        );
    }

    /**
     * Execute and return observation list
     *
     * @param int $planningid - Planning instance id
     * @param int $studentid - User id
     * @return array|array[]
     * @throws \invalid_parameter_exception
     */
    public static function execute(int $planningid, int $studentid): array {
        global $PAGE;

        ['planningid' => $planningid, 'studentid' => $studentid] =
        self::validate_parameters(self::execute_parameters(), ['planningid' => $planningid, 'studentid' => $studentid]);

        $planning = planning::get_record(['id' => $planningid]);
        if (!$planning) {
            throw new \moodle_exception('planningnotfound', 'mod_competvet', '', $planningid);
        }
        $competvet = competvet::get_from_situation_id($planning->get('situationid'));
        self::validate_context($competvet->get_context());

        // Set the page context to be able to call get_user_observations. It is required for fetching the
        // user images.
        $PAGE->set_context(\context_system::instance());

        // This will get the observations for the current user and planning.
        $userobservations = observations::get_user_observations($planningid, $studentid);

        // If there are no observations, we return an empty array.
        $userevals = [];
        foreach ($userobservations as $userobservation) {
            $number = $userobservation['id'];
            $userevals[] = observations::get_observation_information($number);
        }

        // The $userevals array looks like the $example array. We need to transform it to the $return array.
        // The $return array is the data that will be returned by this function. each $userevals item uses the same criteria
        // and the same context. The only difference is the user id and the grade. We need to group the grades by criteria
        // and return the data in the $return array.

        $evals = [];
        foreach ($userevals as $usereval) {
            // get the grader name from the comments. ASK LAURENT FOR A BETTER WAY TO DO THIS.
            $gradername = $usereval['comments'][0]['userinfo']['fullname'];
            foreach ($usereval['criteria'] as $criterion) {
                $criterionid = $criterion['criterioninfo']['id'];
                $criterionname = $criterion['criterioninfo']['label'];
                $grade = $criterion['level'];
                $userid = $usereval['context']['usermodified'];

                if (!isset($evals[$criterionid])) {
                    $evals[$criterionid] = [
                        'name' => $criterionname,
                        'id' => $criterionid,
                        'grades' => [],
                    ];
                }

                $evals[$criterionid]['grades'][] = [
                    'userid' => $userid,
                    'gradername' => $gradername,
                    'value' => $grade,
                ];
            }
        }

        $comments = [];
        foreach ($userevals as $usereval) {
            foreach ($usereval['comments'] as $comment) {
                $userid = $comment['userinfo']['id'];
                $fullname = $comment['userinfo']['fullname'];
                $userpictureurl = $comment['userinfo']['userpictureurl'];
                $commentid = $comment['id'];
                $commenttext = $comment['comment'];
                $timecreated = $comment['timecreated'];

                if (!isset($comments[$userid])) {
                    $comments[$userid] = [
                        'userid' => $userid,
                        'fullname' => $fullname,
                        'picture' => $userpictureurl,
                        'comments' => [],
                    ];
                }

                $comments[$userid]['comments'][] = [
                    'id' => $commentid,
                    'commenttext' => $commenttext,
                    'timecreated' => $timecreated,
                ];
            }
        }

        // The data returned by this function is a grid containing the criteria and the evaluations by others.
        return [
            'evaluations' => array_values($evals),
            'comments' => array_values($comments),
        ];
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'planningid' => new external_value(PARAM_INT, 'Planning instance id', VALUE_REQUIRED),
            'studentid' => new external_value(PARAM_INT, 'Student Id', VALUE_REQUIRED),
        ]);
    }
}
