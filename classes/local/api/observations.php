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
use mod_competvet\local\persistent\situation;
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
     * @param int $userid
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
                'commentformat' => FORMAT_HTML,
                'userinfo' => utils::get_user_info($observation->get('studentid')),
            ];
        }
        $result['comments'] =
            array_values(
                array_map(function($obscrit) {
                    $return = (array) $obscrit->to_record();
                    $return['userinfo'] = utils::get_user_info($return['usercreated']);
                    $return['commentlabel'] = ''; // TODO Fill this in with labels for comment/autoeval.
                    unset($return['usercreated']);
                    $return['comment'] = format_text($return['comment'], $return['commentformat']);
                    unset($return['commentformat']);
                    return $return;
                }, $othercomments)
            );

        $result['criteria'] =
            array_map(function($obscrit) use ($criteria) {
                $criterioninfo = (array) $criteria[$obscrit->get('criterionid')]->to_record();
                unset($criterioninfo['timecreated']);
                unset($criterioninfo['timemodified']);
                unset($criterioninfo['usercreated']);
                $return = [
                    'criterioninfo' => $criterioninfo,
                    'id' => $obscrit->get('id'),
                    'level' => $obscrit->get('level'),
                ];
                $return['subcriteria'] = [];
                return $return;
            }, $observation->get_criteria_levels());

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
            $criterion['subcriteria'] = array_map(function($obscrit) use ($criteria) {
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
        $contextcomment->set('commentformat', FORMAT_HTML);
        $contextcomment->set('usercreated', $studentid);
        $contextcomment->create();
        foreach ($comments as $comment) {
            if (!empty($comment['id'])) {
                $obscomment = observation_comment::get_record(['id' => $comment['id']]);
            } else {
                $obscomment = new observation_comment(0);
                $obscomment->set('observationid', $observation->get('id'));
                $obscomment->set('usercreated', $observerid ?? $USER->id);
                $obscomment->set('type', observation_comment::OBSERVATION_COMMENT);
                $obscomment->create();
            }
            $obscomment->set('comment', $comment['comment']);
            $obscomment->set('commentformat', FORMAT_HTML);
            $obscomment->update();
        }
        // Now create the criteria and subcriteria structure.
        $observationid = $observation->get('id');
        $planning = planning::get_record(['id' => $planningid]);
        $situation = $planning->get_situation();
        $criteriamodels = $situation->get_eval_criteria();
        if (!empty($criteria)) {
            // Flattern the structure so we can easily find the values.
            $criteriadict = [];
            foreach ($criteria as $criterion) {
                $criterionid = $criterion['criterioninfo']['id'] ?? null;
                if (empty($criterionid)) {
                    continue;
                }
                $criteriadict[$criterionid] = $criterion;
                foreach ($criterion['subcriteria'] as $subcriterion) {
                    $subcriterionid = $subcriterion['criterioninfo']['id'] ?? null;
                    if (empty($subcriterionid)) {
                        continue;
                    }
                    $criteriadict[$subcriterionid] = $subcriterion;
                }
            }
        }
        foreach ($criteriamodels as $criterionmodel) {
            if (empty($criterionmodel->get('parentid'))) {
                $obscrit = new observation_criterion_level(0);
                $obscrit->set('observationid', $observationid);
                $obscrit->set('criterionid', $criterionmodel->get('id'));
                if (isset($criteriadict[$criterionmodel->get('id')])) {
                    $obscrit->set('level', $criteriadict[$criterionmodel->get('id')]['level']);
                } else {
                    $obscrit->set('level', 0);
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
     * @param array $context
     * @param array $comments
     * @param array $criteria
     * @return void
     */
    public static function edit_observation(int $observationid, object $context, array $comments, array $criteria) {
        global $USER;
        $observation = observation::get_record(['id' => $observationid]);
        $observation->update();
        if (empty($context->id)) {
            $contextcomment = new observation_comment(0);
            $contextcomment->set('observationid', $observationid);
            $contextcomment->set('type', observation_comment::OBSERVATION_CONTEXT);
            $contextcomment->set('usercreated', $USER->id);
            $contextcomment->create();
        } else {
            $contextcomment = observation_comment::get_record(['id' => $context->id]);
        }
        $contextcomment->set('comment', $context->comment);
        $contextcomment->set('commentformat', FORMAT_HTML);
        $contextcomment->update();
        $commentstodelete = observation_comment::get_records(['observationid' => $observationid,
            'type' => observation_comment::OBSERVATION_COMMENT, ]);
        $commentstodelete = array_combine(
            array_map(fn($comment) => $comment->get('id'), $commentstodelete),
            $commentstodelete
        );
        foreach ($comments as $comment) {
            if (!empty($comment['id'])) {
                $obscomment = observation_comment::get_record(['id' => $comment['id']]);
            } else {
                $obscomment = new observation_comment(0);
                $obscomment->set('observationid', $observationid);
                $obscomment->set('usercreated', $USER->id);
                $obscomment->set('type', observation_comment::OBSERVATION_COMMENT);
                $obscomment->create();
            }
            $obscomment->set('comment', $comment['comment']);
            $obscomment->set('commentformat', FORMAT_HTML);
            $obscomment->update();
            if ($obscomment->get('id')) {
                unset($commentstodelete[$obscomment->get('id')]);
            }
        }
        foreach ($commentstodelete as $comment) {
            $comment->delete();
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
                $updates = true;
            }
            if (isset($criterion['isactive'])) {
                $obscrit->set('level', $criterion['isactive']);
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
