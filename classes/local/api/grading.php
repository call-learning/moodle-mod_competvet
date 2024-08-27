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

use mod_competvet\competvet;
use mod_competvet\local\persistent\planning;
use mod_competvet\utils;
use moodle_url;

global $CFG;
require_once($CFG->libdir . '/gradelib.php');

/**
 * Grading API
 *
 * This is a set of API used both locally by mod_competvet grading system and externally by other plugins.
 *
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class grading {

    /**
     * Get all observations statistics for a given set of planning and for userid
     *
     * @param array $planningsids
     * @param int $userid
     * @return array
     */
    public static function get_planning_infos_for_grading(array $planningsids, int $userid) {
        global $DB;
        if (empty($planningsids)) {
            return [];
        }
        [$where, $params] = $DB->get_in_or_equal($planningsids, SQL_PARAMS_NAMED, 'planningids');
        $plannings = planning::get_records_select("id $where", $params);
        $stats = [];
        foreach ($plannings as $planning) {
            $competvet = competvet::get_from_situation_id($planning->get('situationid'));
            if (!$competvet->has_view_access($userid)) {
                continue;
            }
            $planningid = $planning->get('id');
            $groupstats = self::get_group_infos_for_planning_with_students($planningid);
            $category = plannings::get_category_for_planning_id($planningid);
            $stats[] = [
                'id' => $planning->get('id'),
                'stats' => $groupstats,
                'category' => $category,
                'categorytext' => plannings::get_category_text_for_planning_id($planningid, $category),
            ];
        }
        return $stats;
    }

    /**
     * Retrieves the users which are students associated with all grades for a given planning ID.
     *
     * @param int $planningid The ID of the planning.
     * @return array An array of users.
     */
    protected static function get_students_with_grade_info_for_planning_id(int $planningid): array {
        global $USER;
        $planning = planning::get_record(['id' => $planningid]);
        $competvet = competvet::get_from_situation_id($planning->get('situationid'));
        $students = plannings::get_students_for_planning_id($planningid);
        $groupmembers = [];
        $canseeother = has_capability('mod/competvet:viewother', $competvet->get_context());
        foreach ($students as $student) {
            if (!$canseeother && $student->id != $USER->id) {
                continue;
            }
            $studentgrade = '';
            $grade = $competvet->get_final_grade_for_student($student->id);
            if ($grade->finalgrade) {
                $studentgrade = round($grade->finalgrade, 2);
            }
            $groupmember = clone $student;
            $groupmember->userinfo = utils::get_user_info($student->id);

            // We need the stats per type (so we can display them in the UI).
            $groupmember->planninginfo = plannings::get_planning_stats_for_student($planningid, $student->id, true);
            $groupmember->grade = $studentgrade;
            $groupmember->feedback = format_text($grade->feedback, FORMAT_HTML);
            if ($grade->usermodified) {
                $groupmember->grader = utils::get_user_info($grade->usermodified);
            }
            $groupmember->studenturl = $competvet->get_user_planning_url($student->id, $planningid);
            $groupmember->gradeurl = $competvet->get_user_grading_url($groupmember->id, $planningid);
            $profileparams = [
                'id' => $student->id,
                'course' => $competvet->get_course_id(),
            ];
            $groupmember->profileurl = (new moodle_url('/user/profile.php', $profileparams))->out();
            $groupmembers[] = $groupmember;
        }
        return $groupmembers;
    }

    /**
     * Get all observations statistics for a given planning with student info
     *
     * @param int $planningid
     * @return array|null
     */
    protected static function get_group_infos_for_planning_with_students(int $planningid): ?array {
        $planning = planning::get_record(['id' => $planningid]);
        $stats = ['groupid' => $planning->get('groupid')];
        $students = self::get_students_with_grade_info_for_planning_id($planningid);
        $stats['nbstudents'] = count($students);
        $stats['students'] = $students;
        return $stats;
    }

}
