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
namespace mod_competvet\local\api;

use mod_competvet\local\persistent\observation;
use mod_competvet\local\persistent\observation_comment;
use mod_competvet\local\persistent\observation_criterion_comment;
use mod_competvet\local\persistent\observation_criterion_level;
use mod_competvet\local\persistent\planning;
use mod_competvet\utils;

/**
 * Observation  API
 *
 *
 * This is a set of API used both locally by mod_competvet and local_competvet
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observations {
    /**
     * Get all observations for a given planning
     *
     * @param int $planningid
     * @param int $userid
     * @return array
     */
    public static function get_user_observations(int $planningid, int $userid): array {
        $observations =
            observation::get_records(['planningid' => $planningid, 'studentid' => $userid]);
        $evalobservations = [];
        foreach ($observations as $observation) {
            $category = $observation->get_observation_type();
            $evalobservations[] = [
                'id' => $observation->get('id'),
                'studentinfo' => utils::get_user_info($observation->get('studentid')),
                'observerinfo' => utils::get_user_info($observation->get('observerid')),
                'isautoeval' => $observation->is_autoeval(),
                'status' => $observation->get('status'),
                'time' => $observation->get('timemodified'),
                'category' => $category,
                'categorytext' => get_string('observation:category:' . observation::CATEGORIES[$category], 'competvet'),
            ];
        }
        return $evalobservations;
    }

    /**
     * Get observation information
     *
     * @param int $observationid
     * @return array
     */
    public static function get_observation_information(int $observationid): array {
        // To be replaced asap by a system report.
        $observation =
            observation::get_record(['id' => $observationid]);
        $planning = planning::get_record(['id' => $observation->get('planningid')]);
        $situation = $planning->get_situation();
        $criteria = $situation->get_eval_criteria();
        $criteria = array_combine(
            array_map(fn($crit) => $crit->get('id'), $criteria),
            $criteria
        );
        $comments = $observation->get_comments();
        $contexts = array_filter($comments, fn($comment) => $comment->get('type') == observation_comment::OBSERVATION_CONTEXT);
        $context = end($contexts);
        $othercomments = array_filter($comments, fn($comment) => $comment->get('type') != observation_comment::OBSERVATION_CONTEXT);
        $contextrecord = [];
        $result = [
            'id' => $observation->get('id'),
            'category' => $observation->get_observation_type(),
            'grader' => $observation->get('observerid'),
        ];
        if (!empty($context)) {
            $contextrecord = $context->to_record();
            $contextrecord->userinfo = utils::get_user_info($context->get('usercreated'));
            $contextrecord->comment = format_text($contextrecord->comment, $contextrecord->commentformat);
            unset($contextrecord->commentformat);
            $contextrecord = (array) $contextrecord;
            $result['context'] = $contextrecord;
        } else {
            $result['context'] = [
                'comment' => '',
                'commentformat' => FORMAT_PLAIN,
                'userinfo' => utils::get_user_info($observation->get('studentid')),
            ];
        }
        $result['comments'] =
            array_values(
                array_map(function ($obscrit) {
                    $return = (array) $obscrit->to_record();
                    $return['userinfo'] = utils::get_user_info($return['usercreated']);
                    $return['commentlabel'] = ''; // TODO Fill this in with labels for comment/autoeval.
                    unset($return['usercreated']);
                    $return['comment'] = format_text($return['comment'], $return['commentformat']);
                    unset($return['commentformat']);
                    return $return;
                }, $othercomments)
            );

        $result['criteria'] = array_values(
            array_map(function ($obscrit) use ($criteria) {
                $criterioninfo = (array) $criteria[$obscrit->get('criterionid')]->to_record();
                unset($criterioninfo['timecreated']);
                unset($criterioninfo['timemodified']);
                unset($criterioninfo['usercreated']);
                $return = [
                    'criterioninfo' => $criterioninfo,
                    'id' => $obscrit->get('id'),
                    'level' => $obscrit->get('level'),
                    'isactive' => $obscrit->get('isactive'),
                ];
                $return['subcriteria'] = [];
                return $return;
            }, $observation->get_criteria_levels())
        );

        $allcomments = $observation->get_criteria_comments();
        foreach ($result['criteria'] as &$criterion) {
            $allchildrencriteria =
                array_filter($criteria, fn($crit) => $crit->get('parentid') == $criterion['criterioninfo']['id']);
            $allchildrencriteriaid = array_map(fn($crit) => $crit->get('id'), $allchildrencriteria);
            $subcriteriacomments =
                array_values(array_filter(
                    $allcomments,
                    fn($comment) => in_array($comment->get('criterionid'), $allchildrencriteriaid)
                ));
            $criterion['subcriteria'] = array_map(function ($obscrit) use ($criteria) {
                $return = [
                    'criterioninfo' => (array) $criteria[$obscrit->get('criterionid')]->to_record(),
                    'comment' => $obscrit->get('comment'),
                    'id' => $obscrit->get('id'),
                ];
                return $return;
            }, $subcriteriacomments);
        }
        $result['canedit'] = $observation->can_edit();
        $result['candelete'] = $observation->can_delete();
        return $result;
    }

    /**
     * Create an observation
     *
     * @param int $category
     * @param int $planningid
     * @param int $studentid
     * @param int|null $observerid
     * @param string|null $context
     * @return int
     */
    public static function create_observation(
        int $category,
        int $planningid,
        int $studentid,
        ?int $observerid = 0,
        ?string $context = null,
        ?array $comments = [],
        ?array $criteria = []
    ): int {
        global $USER;
        $observation = new observation(0);
        $observation->set('category', $category);
        $observation->set('studentid', $studentid);
        $observation->set('planningid', $planningid);
        $observation->set('observerid', $observerid);
        $observation->set('status', observation::STATUS_NOTSTARTED);
        $observation->create();
        $contextcomment = new observation_comment(0);
        $contextcomment->set('observationid', $observation->get('id'));
        $contextcomment->set('type', observation_comment::OBSERVATION_CONTEXT);
        $contextcomment->set('comment', $context ?? '');
        $contextcomment->set('commentformat', FORMAT_PLAIN);
        $contextcomment->set('usercreated', $studentid);
        $contextcomment->create();
        foreach ($comments as $comment) {
            if (!empty($comment['id'])) {
                $obscomment = observation_comment::get_record(['id' => $comment['id']]);
            } else {
                $obscomment = new observation_comment(0);
                $obscomment->set('observationid', $observation->get('id'));
                $obscomment->set('usercreated', $observerid ?? $USER->id);
                $obscomment->set('type', $comment['type'] ?? observation_comment::OBSERVATION_COMMENT);
                $obscomment->create();
            }
            $obscomment->set('comment', $comment['comment']);
            $obscomment->set('commentformat', FORMAT_PLAIN);
            $obscomment->update();
        }
        // Now create the criteria and subcriteria structure.
        $observationid = $observation->get('id');
        $planning = planning::get_record(['id' => $planningid]);
        $situation = $planning->get_situation();
        $criteriamodels = $situation->get_eval_criteria();
        if (!empty($criteria)) {
            // Flattern the structure so we can easily find the values.
            // Note here we accept both flat list of criteria [['id' => 1, 'level' => 2], ['id' => 2, 'comment' => 'comment']]
            // or the tree like list [['id' => 1, 'level' => 2], 'subcriteria' => [['id' => 2, 'comment' => 'comment']]]].
            $criteriadict = [];
            foreach ($criteria as $criterion) {
                $criterionid = $criterion['criterioninfo']['id'] ?? $criterion['id'] ?? null;
                if (empty($criterionid)) {
                    continue;
                }
                $criteriadict[$criterionid] = $criterion;
                if (isset($criterion['subcriteria'])) {
                    foreach ($criterion['subcriteria'] as $subcriterion) {
                        $subcriterionid = $subcriterion['criterioninfo']['id'] ?? null;
                        if (empty($subcriterionid)) {
                            continue;
                        }
                        $criteriadict[$subcriterionid] = $subcriterion;
                    }
                }
            }
        }
        foreach ($criteriamodels as $criterionmodel) {
            if (empty($criterionmodel->get('parentid'))) {
                $obscrit = new observation_criterion_level(0);
                $obscrit->set('observationid', $observationid);
                $obscrit->set('criterionid', $criterionmodel->get('id'));
                if (isset($criteriadict[$criterionmodel->get('id')])) {
                    $obscrit->set('level', $criteriadict[$criterionmodel->get('id')]['level'] ?? null);
                    $obscrit->set('isactive', $criteriadict[$criterionmodel->get('id')]['isactive'] ?? false);
                }
                $obscrit->create();
            } else {
                $obscrit = new observation_criterion_comment(0);
                $obscrit->set('observationid', $observationid);
                $obscrit->set('criterionid', $criterionmodel->get('id'));
                if (isset($criteriadict[$criterionmodel->get('id')])) {
                    $obscrit->set('comment', $criteriadict[$criterionmodel->get('id')]['comment']);
                } else {
                    $obscrit->set('comment', '');
                }
                $obscrit->create();
            }
        }
        return $observation->get('id');
    }

    /**
     * Edit an observation
     *
     * @param int $observationid
     * @param object|null $context
     * @param array $comments
     * @param array $criteria
     * @return void
     */
    public static function edit_observation(
        int $observationid,
        string $context = null,
        array $comments = [],
        array $criteria = [],
    ) {
        global $USER;
        $observation = observation::get_record(['id' => $observationid]);
        if (!$observation) {
            throw new \moodle_exception('invalidobservationid', 'competvet');
        }
        if ($context) {
            $existing = self::get_and_normalise_comments($observationid, observation_comment::OBSERVATION_CONTEXT);
            if (!$existing) {
                $existing = new observation_comment(0);
                $existing->set('observationid', $observationid);
                $existing->set('type', observation_comment::OBSERVATION_CONTEXT);
                $existing->set('usercreated', $USER->id);
                $existing->create();
            }
            $existing->set('comment', $context);
            $existing->set('commentformat', FORMAT_PLAIN);
            $existing->update();
        }
        foreach ($comments as $comment) {
            if (!empty($comment['id'])) {
                $obscomment = observation_comment::get_record(['id' => $comment['id']]);
            } else {
                $obscomment = self::get_and_normalise_comments($observationid, $comment['type']);
                if (!$obscomment) {
                    $obscomment = new observation_comment(0);
                    $obscomment->set('observationid', $observationid);
                    $obscomment->set('usercreated', $USER->id);
                    $obscomment->set('type', $comment['type'] ?? observation_comment::OBSERVATION_COMMENT);
                    $obscomment->create();
                }
            }
            $obscomment->set('comment', $comment['comment']);
            $obscomment->set('commentformat', FORMAT_PLAIN);
            $obscomment->update();
        }
        // We are now sure we have the full structure of criteria.
        foreach ($criteria as $criterion) {
            if (isset($criterion['id'])) {
                $obscrit = observation_criterion_level::get_record(['id' => $criterion['id']]);
            } else {
                $obscrit = new observation_criterion_level(0);
                $obscrit->set('observationid', $observationid);
                $obscrit->set('criterionid', $criterion['criterionid']);
                $obscrit->create();
            }
            $updates = false;
            if (isset($criterion['level'])) {
                $obscrit->set('level', $criterion['level']);
                $obscrit->set('isactive', true);
                $updates = true;
            }
            if (isset($criterion['isactive'])) {
                $obscrit->set('isactive', $criterion['isactive']);
                $updates = true;
            }
            if ($updates) {
                $obscrit->update();
            }
            foreach ($criterion['subcriteria'] as $subcriterion) {
                if (isset($subcriterion['id'])) {
                    $obscrit = observation_criterion_comment::get_record(['id' => $subcriterion['id']]);
                } else {
                    $obscrit = new observation_criterion_comment(0);
                    $obscrit->set('observationid', $observationid);
                    $obscrit->set('criterionid', $criterion['criterionid']);
                    $obscrit->create();
                }
                if (isset($subcriterion['comment'])) {
                    $obscrit->set('comment', $subcriterion['comment']);
                    $obscrit->update();
                }
            }
        }
    }

    private static function get_and_normalise_comments(int $observationid, int $commentype): ?observation_comment {
        $comments = observation_comment::get_records(['observationid' => $observationid, 'type' => $commentype]);
        if (empty($comments)) {
            return null;
        }
        if (count($comments) > 1) {
            while (count($comments) > 1) {
                $comment = array_shift($comments);
                $comment->delete();
            }
        }
        return array_shift($comments);
    }

    /**
     * Delete an observation
     *
     * @param int $observationid
     * @return void
     */
    public static function delete_observation(int $observationid): void {
        $observation = observation::get_record(['id' => $observationid]);
        foreach (observation_comment::get_records(['observationid' => $observationid]) as $comment) {
            $comment->delete();
        }
        foreach (observation_criterion_level::get_records(['observationid' => $observationid]) as $criterion) {
            $criterion->delete();
        }
        foreach (observation_criterion_comment::get_records(['observationid' => $observationid]) as $criterion) {
            $criterion->delete();
        }
        $observation->delete();
    }
}
