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
use mod_competvet\local\persistent\observation;
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
     * Returns description of method return value
     *
     * @return external_description
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure
        (
            [
                'hasautoevaluations' => new external_value(PARAM_BOOL, 'Has autoevaluations'),
                'hasobserverevaluations' => new external_value(PARAM_BOOL, 'Has observer evaluations'),
                'totalaverage' => new external_value(PARAM_INT, 'Total average'),
                'evaluations' => new external_multiple_structure(
                    new external_single_structure(
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
                                        'obsid' => new external_value(PARAM_INT, 'Observation id'),
                                        'level' => new external_value(PARAM_INT, 'Grade', VALUE_OPTIONAL),
                                        'autoeval' => new external_value(PARAM_BOOL, 'Is autoeval', VALUE_OPTIONAL),
                                        'graderinfo' => new external_single_structure(
                                            [
                                                'id' => new external_value(PARAM_INT, 'Grader id'),
                                                'fullname' => new external_value(PARAM_TEXT, 'Grader full name'),
                                                'userpictureurl' => new external_value(PARAM_URL, 'Grader picture url'),
                                            ]
                                            , 'grader info', VALUE_OPTIONAL),
                                        'timemodified' => new external_value(PARAM_TEXT, 'Date', VALUE_OPTIONAL),
                                        'date' => new external_value(PARAM_TEXT, 'Date', VALUE_OPTIONAL),
                                    ]
                                )
                            ),
                            'hasaverage' => new external_value(PARAM_BOOL, 'Has average', VALUE_OPTIONAL),
                            'average' => new external_value(PARAM_INT, 'Average', VALUE_OPTIONAL),
                        ]
                    ),
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
        $userobservations = observations::get_user_observations($planningid, $studentid, true);

        $gradedcriteria = [];
        $comments = [];
        $hasautoevaluations = false;
        $hasobserverevaluations = false;
        foreach ($userobservations as $userobservation) {
            self::collect_grades($userobservation, $gradedcriteria);
            self::collect_comments($userobservation, $comments);
            self::collect_subcriteria_comments($userobservation, $comments);
            if ($userobservation['category'] == observation::CATEGORY_EVAL_OBSERVATION) {
                $hasobserverevaluations = true;
            } else {
                $hasautoevaluations = true;
            }
        }
        $gradedcriteria = array_filter($gradedcriteria, function ($criterion) {
            return !empty($criterion['grades']);
        });
        // Now process the average and mean for grades.
        $totalaverage = 0;
        $totalaveragecount = 0;
        foreach($gradedcriteria as $index => $gradedcriterion) {
            $gradesum = 0;
            $gradescount = 0;
            $average = 0;
            $hasaverage = false;
            foreach ($gradedcriterion['grades'] as $grade) {
                if (!$grade['autoeval'] && $grade['level'] !== null) {
                    $gradesum += $grade['level'] ?? 0;
                    $gradescount++;
                }
            }
            if ($gradesum > 0) {
                $hasaverage = true;
                $average = $gradesum / $gradescount;
            }
            if ($hasaverage) {
                $totalaverage += $average;
                $totalaveragecount++;
            }
            $gradedcriteria[$index]['hasaverage'] = $hasaverage;
            $gradedcriteria[$index]['average'] = round($average);
        }
        usort($gradedcriteria, function ($a, $b) {
            return $a['criterion']['sort'] <=> $b['criterion']['sort'];
        });
        // The data returned by this function is a grid containing the criteria and the evaluations by others.
        return [
            'evaluations' => array_values($gradedcriteria),
            'hasobserverevaluations' => $hasobserverevaluations,
            'hasautoevaluations' => $hasautoevaluations,
            'evalcomments' => $comments,
            'totalaverage' => $totalaveragecount > 0 ? round($totalaverage / $totalaveragecount) : 0,
        ];
    }

    /**
     * Collect grades from an observation
     *
     * @param array $userobservation
     * @param array $gradedcriteria
     * @return void
     */
    private static function collect_grades(array $userobservation, array &$gradedcriteria): void {
        foreach ($userobservation['criteria'] as $gradedcriterion) {
            $criterionid = $gradedcriterion['criterioninfo']['id'];
            $grade = [
                'obsid' => $userobservation['id'],
                'level' => $gradedcriterion['level'],
                'autoeval' => $userobservation['category'] == observation::CATEGORY_EVAL_AUTOEVAL,
                'timemodified' => $userobservation['timemodified'],
                'date' => userdate($userobservation['timemodified'], get_string('strftimedatefullshort')),
                'graderinfo' => $userobservation['observerinfo'],
            ];
            if (!isset($gradedcriteria[$criterionid])) {
                $gradedcriteria[$criterionid] = [
                    'criterion' => $gradedcriterion['criterioninfo'],
                    'grades' => [],
                ];
            }
            $gradedcriteria[$criterionid]['grades'][] = $grade;
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
