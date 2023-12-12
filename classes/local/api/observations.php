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
use mod_competvet\local\persistent\planning;
use mod_competvet\local\persistent\situation;

/**
 * Observation  API
 *
 * TODO Rename this to evaluation as it is more generic and will involve eval, certif and list
 * TODO Rework on the comment structure, so we have one comment type in the DB instead of context/comment
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
    public static function get_user_evaluation(int $planningid, int $userid): array {
        $result = [];
        // To be replaced asap by a system report.
        $observations =
            observation::get_records(['planningid' => $planningid, 'studentid' => $userid]);
        $evalobservations = [];
        foreach ($observations as $observation) {
            $category = $observation->get_observation_type();
            $evalobservations[] = [
                'id' => $observation->get('id'),
                'studentid' => $observation->get('studentid'),
                'observerid' => $observation->get('observerid'),
                'status' => $observation->get('status'),
                'time' => $observation->get('timemodified'),
                'category' => $category,
                'categorytext' => get_string('observation:category:' . observation::CATEGORIES[$category], 'competvet'),
            ];
        }
        if (!empty($evalobservations)) {
            $result['eval'] = $evalobservations;
        }
        return $result;
    }

    /**
     * Get planning info for student
     *
     * @param int $planningid
     * @param int $userid
     * @return array
     */
    public static function get_planning_info_for_student(int $planningid, int $userid): array {
        $params = ['planningid' => $planningid, 'status' => observation::STATUS_COMPLETED, 'studentid' => $userid];
        $observations =
            observation::get_records($params, 'studentid, observerid');
        $result = [];
        $planning = planning::get_record(['id' => $planningid]);
        $situation = situation::get_record(['id' => $planning->get('situationid')]);
        $result[$userid] = self::create_planning_info_for_student($userid, $situation, $observations);
        return $result;
    }

    /**
     * Creates planning information for a student.
     *
     * @param int $studentid The ID of the student.
     * @param situation $situation The situation object.
     * @param array $existingobservations An array of existing observations.
     * @return array The planning information for the student.
     */
    protected static function create_planning_info_for_student(int $studentid, situation $situation, array $existingobservations) {
        $result = [
            'id' => $studentid,
            'info' => [],
        ];
        // Check for eval.
        $eval = [
            'type' => 'eval',
            'nbdone' => 0,
            'nbrequired' => $situation->get('evalnum'),
        ];
        $autoeval = [
            'type' => 'autoeval',
            'nbdone' => 0,
            'nbrequired' => $situation->get('autoevalnum'),
        ];
        foreach ($existingobservations as $observation) {
            if ($observation->get('studentid') != $studentid) {
                continue;
            }
            if ($observation->get_observation_type() == observation::CATEGORY_EVAL_AUTOEVAL) {
                $autoeval['nbdone']++;
            } else {
                $eval['nbdone']++;
            }
        }
        $result['info'][] = $eval;
        $result['info'][] = $autoeval;
        return $result;
    }

    /**
     * Get planning info for students
     *
     * @param int $planningid
     * @return array
     */
    public static function get_planning_info_for_students(int $planningid): array {
        $params = ['planningid' => $planningid, 'status' => observation::STATUS_COMPLETED];
        $observations =
            observation::get_records($params, 'studentid, observerid');
        $results = [];
        $planning = planning::get_record(['id' => $planningid]);
        $situation = situation::get_record(['id' => $planning->get('situationid')]);
        $students = plannings::get_students_for_planning_id($planningid);
        foreach ($students as $student) {
            $results[$student->id] = self::create_planning_info_for_student($student->id, $situation, $observations);
        }
        return $results;
    }

    /**
     * Get all observations for a given planning
     *
     * @param int $observationid
     * @param int $userid
     * @return array
     */
    public static function get_observation_information(int $observationid): array {
        // To be replaced asap by a system report.
        $observation =
            observation::get_record(['id' => $observationid]);

        $result = [
            'id' => $observation->get('id'),
            'category' => $observation->get_observation_type(),
            'comments' =>
                array_map(function ($obs) {
                    return $obs->to_record();
                }, $observation->get_comments()),
            'criterialevels' => array_map(function ($obs) {
                return $obs->to_record();
            }, $observation->get_criteria_levels()),
            'criteriacomments' => array_map(function ($obs) {
                return $obs->to_record();
            },
                $observation->get_criteria_comments()),
        ];
        return $result;
    }
}
