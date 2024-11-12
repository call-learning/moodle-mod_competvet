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

use core_reportbuilder\local\filters\number;
use external_api;
use external_description;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;
use mod_competvet\competvet;
use mod_competvet\local\api\observations;
use mod_competvet\local\api\todos;
use mod_competvet\local\persistent\observation;
use mod_competvet\local\persistent\observation_criterion_level;
use mod_competvet\local\persistent\todo;
use mod_competvet\local\persistent\observation_comment;
use mod_competvet\local\persistent\planning;
use mod_competvet\local\api\criteria;
use mod_competvet\utils;

/**
 * Class get_evaluations
 *
 * @package    mod_competvet
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_evaluations extends external_api {
    /**
     * Get grading structure for autoeval or observations.
     *
     * @return external_single_structure
     */
    private static function get_grade_structure(): external_single_structure {
        return new external_single_structure(
            [
                'criterion' => new external_single_structure(
                    [
                        'id' => new external_value(PARAM_INT, 'Criterion id'),
                        'label' => new external_value(PARAM_TEXT, 'Criterion name'),
                    ]
                ),
                'grades' => new external_multiple_structure(
                    new external_single_structure(
                        [
                            'obsid' => new external_value(PARAM_INT, 'Observation id', VALUE_OPTIONAL),
                            'level' => new external_value(PARAM_INT, 'Grade', VALUE_OPTIONAL),
                            'graderinfo' => new external_single_structure(
                                [
                                    'id' => new external_value(PARAM_INT, 'Grader id'),
                                    'fullname' => new external_value(PARAM_TEXT, 'Grader full name'),
                                    'userpictureurl' => new external_value(PARAM_URL, 'Grader picture url'),
                                ],
                                'grader info',
                                VALUE_OPTIONAL
                            ),
                            'timemodified' => new external_value(PARAM_TEXT, 'Date', VALUE_OPTIONAL),
                            'date' => new external_value(PARAM_TEXT, 'Date', VALUE_OPTIONAL),
                            'nograde' => new external_value(PARAM_BOOL, 'No grade', VALUE_OPTIONAL),
                        ]
                    )
                ),
                'hasaverage' => new external_value(PARAM_BOOL, 'Has average', VALUE_OPTIONAL),
                'average' => new external_value(PARAM_INT, 'Average', VALUE_OPTIONAL),
            ]
        );
    }

    /**
     * Returns description of method return value
     *
     * @return external_description
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure(
            [
                'hasautoevaluations' => new external_value(PARAM_BOOL, 'Has autoevaluations'),
                'hasobserverevaluations' => new external_value(PARAM_BOOL, 'Has observer evaluations'),
                'hasanyobservations' => new external_value(PARAM_BOOL, 'Has any observations'),
                'totalaverage' => new external_value(PARAM_INT, 'Total average'),
                'numberofobservations' => new external_value(PARAM_INT, 'Number of observations'),
                'observations' => new external_multiple_structure(
                    self::get_grade_structure()
                ),
                'autoevals' => new external_multiple_structure(
                    self::get_grade_structure()
                ),
                'evalcomments' => new external_multiple_structure(
                    new external_single_structure(
                        [
                            'isautoeval' => new external_value(PARAM_BOOL, 'Is autoeval'),
                            'observerinfo' => new external_single_structure(
                                [
                                    'id' => new external_value(PARAM_INT, 'Observer id'),
                                    'fullname' => new external_value(PARAM_TEXT, 'Observer full name'),
                                    'userpictureurl' => new external_value(PARAM_URL, 'Observer picture url'),
                                ]
                            ),
                            'comments' => new external_multiple_structure(
                                new external_single_structure(
                                    [
                                        'id' => new external_value(PARAM_INT, 'Comment id'),
                                        'comment' => new external_value(PARAM_RAW, 'Comment'),
                                        'timecreated' => new external_value(PARAM_INT, 'Time created'),
                                        'commenttitle' => new external_value(PARAM_TEXT, 'Comment title'),
                                        'private' => new external_value(PARAM_BOOL, 'Private comment', VALUE_OPTIONAL),
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

        ['planningid' => $planningid, 'studentid' => $studentid] =
            self::validate_parameters(self::execute_parameters(), ['planningid' => $planningid, 'studentid' => $studentid]);

        $planning = planning::get_record(['id' => $planningid]);
        if (!$planning) {
            throw new \moodle_exception('planningnotfound', 'mod_competvet', '', $planningid);
        }
        $competvet = competvet::get_from_situation_id($planning->get('situationid'));
        self::validate_context($competvet->get_context());

        // This will get the observations for the current user and planning.
        $userobservations = observations::get_user_observations($planningid, $studentid, true);

        $comments = [];
        $hasautoevaluations = false;
        $hasobserverevaluations = false;
        $criteria = $competvet->get_situation()->get_eval_criteria();
        // This will get the todos targetted to the current user.
        $todos = todos::get_todos_for_target_user_on_planning($planningid, $studentid, todo::ACTION_EVAL_OBSERVATION_ASKED);
        self::collect_todos($todos, $comments);
        $gradedobservations = [];
        $gradedautoevals = [];
        $numberofobservations = 0;
        foreach ($userobservations as $userobservation) {
            self::collect_grades($userobservation, $criteria, $gradedobservations, $gradedautoevals);
            self::collect_comments($userobservation, $comments);
            self::collect_subcriteria_comments($userobservation, $comments);
            if ($userobservation['category'] == observation::CATEGORY_EVAL_OBSERVATION) {
                $hasobserverevaluations = true;
                $numberofobservations++;
            } else {
                $hasautoevaluations = true;
            }
        }
        // Now process the average and mean for grades.
        [$totalaverage, $totalaveragecount] = self::compute_average_and_stats($gradedobservations);
        self::compute_average_and_stats($gradedautoevals);
        $gradedobservations = self::remove_empty_rows($gradedobservations);
        $gradedautoevals = self::remove_empty_rows($gradedautoevals);
        usort($gradedobservations, function ($a, $b) {
            return $a['criterion']['sort'] <=> $b['criterion']['sort'];
        });
        usort($gradedautoevals, function ($a, $b) {
            return $a['criterion']['sort'] <=> $b['criterion']['sort'];
        });

        // The data returned by this function is a grid containing the criteria and the evaluations by others.
        return [
            'autoevals' => array_values($gradedautoevals),
            'observations' => array_values($gradedobservations),
            'hasobserverevaluations' => $hasobserverevaluations,
            'hasautoevaluations' => $hasautoevaluations,
            'hasanyobservations' => $hasautoevaluations || $hasobserverevaluations,
            'evalcomments' => $comments,
            'totalaverage' => $totalaveragecount > 0 ? round($totalaverage / $totalaveragecount) : 0,
            'numberofobservations' => $numberofobservations,
        ];
    }


    private static function remove_empty_rows($gradedcriteria) {
        return array_filter($gradedcriteria, function ($criterion) {
            return !array_reduce(
                $criterion['grades'],
                fn($acc, $grade) => $acc && ($grade['nograde']),
                true
            );
        });
    }
    private static function compute_average_and_stats(&$gradedcriteria) {
        $totalaverage = 0;
        $totalaveragecount = 0;
        foreach ($gradedcriteria as $index => $gradedobservation) {
            $gradesum = null;
            $gradescount = 0;
            $average = 0;
            $hasaverage = false;
            foreach ($gradedobservation['grades'] as $grade) {
                if (!$grade['nograde']) {
                    $gradesum += $grade['level'] ?? 0;
                    $gradescount++;
                }
            }
            if ($gradesum !== null && $gradesum >= 0) {
                $hasaverage = true;
                $average = $gradesum / $gradescount;
            }
            if ($hasaverage) {
                $totalaverage += $average;
                $totalaveragecount++;
            }
            $gradedcriteria[$index]['hasaverage'] = $hasaverage;
            if ($hasaverage) {
                $gradedcriteria[$index]['average'] = round($average);
            } else {
                $gradedcriteria[$index]['average'] = null;
            }
        }
        return [$totalaverage, $totalaveragecount];
    }
    /**
     * Collect grades from an observation
     *
     * @param array $userobservation
     * @param array $criteria
     * @param array $gradedcriteria
     * @return void
     */
    private static function collect_grades(array $userobservation, array $criteria, array &$gradedobservations, &$gradedautoevals): void {
        $observedcriteriaid = array_map(
            function ($observedcriterion) {
                return $observedcriterion['criterioninfo']['id'];
            },
            $userobservation['criteria']
        );
        $observationbycriteria = array_combine($observedcriteriaid, $userobservation['criteria']);
        foreach ($criteria as $criterion) {
            $criterionid = $criterion->get('id');
            $gradedcriterion = $observationbycriteria[$criterionid] ?? null;
            if (!$gradedcriterion || observation_criterion_level::is_an_empty_level($gradedcriterion['level'])) {
                $grade = [
                    'obsid' => $userobservation['id'],
                    'graderinfo' => $userobservation['observerinfo'],
                    'timemodified' => $userobservation['timemodified'],
                    'date' => userdate($userobservation['timemodified'], get_string('strftimedatefullshort')),
                    'nograde' => true
                ];
            } else {
                $grade = [
                    'obsid' => $userobservation['id'],
                    'level' => $gradedcriterion['level'],
                    'timemodified' => $userobservation['timemodified'],
                    'date' => userdate($userobservation['timemodified'], get_string('strftimedatefullshort')),
                    'graderinfo' => $userobservation['observerinfo'],
                    'nograde' => false,
                ];
            }
            if ($userobservation['category'] == observation::CATEGORY_EVAL_AUTOEVAL) {
                if (!isset($gradedautoevals[$criterionid])) {
                    $gradedautoevals[$criterionid] = [
                        'criterion' => (array) $criterion->to_record(),
                        'grades' => [],
                    ];
                }
                $gradedautoevals[$criterionid]['grades'][] = $grade;
            } else {
                if (!isset($gradedobservations[$criterionid])) {
                    $gradedobservations[$criterionid] = [
                        'criterion' => (array) $criterion->to_record(),
                        'grades' => [],
                    ];
                }
                $gradedobservations[$criterionid]['grades'][] = $grade;
            }
        }
    }

    /**
     * Collect comments from an observation
     *
     * @param array $userobservation
     * @param array $comments
     * @return void
     */
    private static function collect_comments(array $userobservation, array &$comments): void {
        $observerid = $userobservation['observerinfo']['id'];
        foreach ($userobservation['comments'] as $comment) {
            if (!isset($comments[$observerid])) {
                $comments[$observerid] = [
                    'observerinfo' => $userobservation['observerinfo'],
                    'isautoeval' => $userobservation['category'] == observation::CATEGORY_EVAL_AUTOEVAL,
                    'comments' => [],
                ];
            }
            if (empty(trim($comment['comment']))) {
                continue;
            }
            $comments[$observerid]['comments'][] = [
                'id' => $comment['id'],
                'comment' => $comment['comment'],
                'timecreated' => $comment['timecreated'],
                'commenttitle' => $comment['label'],
                'private' => $comment['private'],
            ];
        }
    }

    /**
     * Collect todos from an observation
     *
     * @param array $todos
     * @param array $comments
     * @return void
     */
    private static function collect_todos(array $todos, array &$comments): void {
        foreach ($todos as $todo) {
            $observerid = $todo['user']['id'];
            if (!isset($comments[$observerid])) {
                $comments[$observerid] = [
                    'observerinfo' => $todo['user'],
                    'isautoeval' => false,
                    'comments' => [],
                ];
            }

            $strings = [
                'timecreated' => userdate($todo['timecreated'], get_string('strftimedatefullshort')),
                'timemodified' => userdate($todo['timemodified'], get_string('strftimedatefullshort')),
                'userfullname' => $todo['user']['fullname'],
                'targetfullname' => $todo['targetuser']['fullname'],
            ];
            if ($todo['status'] == todo::STATUS_PENDING) {
                $comments[$observerid]['comments'][] = [
                    'id' => $todo['id'],
                    'comment' => get_string('observationwaiting', 'mod_competvet', $strings),
                    'timecreated' => 0,
                    'commenttitle' => get_string('observationrequest', 'mod_competvet'),
                    'private' => false,
                ];
            } else {
                $comments[$observerid]['comments'][] = [
                    'id' => $todo['id'],
                    'comment' => get_string('observationrequested', 'mod_competvet', $strings),
                    'timecreated' => 0,
                    'commenttitle' => get_string('observationrequest', 'mod_competvet'),
                    'private' => false,
                ];
            }
        }
    }

    /**
     * Collect commments from subcriteria
     *
     * @param array $userobservation
     * @param array $comments
     * @return void
     */
    private static function collect_subcriteria_comments(array $userobservation, array &$comments): void {
        $observerid = $userobservation['observerinfo']['id'];
        foreach ($userobservation['criteria'] as $gradedcriterion) {
            foreach ($gradedcriterion['subcriteria'] as $commentedsubcriteria) {
                if (!isset($comments[$observerid])) {
                    $comments[$observerid] = [
                        'observerinfo' => $userobservation['observerinfo'],
                        'isautoeval' => $userobservation['category'] == observation::CATEGORY_EVAL_AUTOEVAL,
                        'comments' => [],
                    ];
                }
                if (empty(trim($commentedsubcriteria['comment']))) {
                    continue;
                }
                $comments[$observerid]['comments'][] = [
                    'id' => $commentedsubcriteria['id'],
                    'comment' => $commentedsubcriteria['comment'],
                    'timecreated' => $commentedsubcriteria['timecreated'],
                    'commenttitle' => $commentedsubcriteria['criterioninfo']['label'],
                    'private' => false,
                ];
            }
        }
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
