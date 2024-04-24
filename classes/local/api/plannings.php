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
use mod_competvet\local\persistent\observation;
use mod_competvet\local\persistent\planning;
use mod_competvet\local\persistent\situation;
use mod_competvet\utils;

/**
 * Plannings API
 *
 * This is a set of API used both locally by mod_competvet and local_competvet
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class plannings {
    /**
     * Planning fields managed in the API
     */
    const API_PLANNING_FIELDS = ['id', 'situationid', 'startdate', 'enddate', 'session', 'session', 'groupid', 'groupname'];

    /**
     * Get planning for a given situation ID
     *
     * @param int $situationid situation ID
     * @param int $userid user ID
     * @param bool $addstats add stats to the plannning
     * @param bool $nofuture do not show future situation
     * @return array array of plannings
     */
    public static function get_plannings_for_situation_id(
        int $situationid,
        int $userid,
        bool $nofuture = true
    ): array {
        // Check if user has access to this situation, else throw an error.
        $competvet = competvet::get_from_situation_id($situationid);
        if (!$competvet->has_view_access($userid)) {
            return [];
        }
        $situationcontext = $competvet->get_context();

        $isstudent = utils::is_student($userid, $situationcontext->id);
        $planningfilters = [
            'situationid' => $situationid,
        ];
        $planninngssql = 'situationid = :situationid';
        if ($isstudent) {
            global $DB;
            // Remove planning for which this user is not involved.
            $allusergroups = groups_get_all_groups($situationcontext->get_course_context()->instanceid, $userid);
            $allusergroupsid = array_keys($allusergroups);
            [$sql, $params] = $DB->get_in_or_equal($allusergroupsid, SQL_PARAMS_NAMED, 'allusergroupsid');
            $planninngssql .= " AND groupid $sql";
            $planningfilters = array_merge($planningfilters, $params);
        }
        if ($nofuture) {
            $planningfilters['startdate'] = (new \DateTime('-1 hour'))->getTimestamp();
            $planninngssql .= " AND startdate < :startdate";
        }
        $allplannings = planning::get_records_select($planninngssql, $planningfilters);
        $plannings = [];
        foreach ($allplannings as $planning) {
            $newplanning = (array) $planning->to_record();
            $newplanning['groupname'] = groups_get_group_name($planning->get('groupid'));
            $newplanning = array_intersect_key($newplanning, array_fill_keys(self::API_PLANNING_FIELDS, 0));
            $plannings[] = $newplanning;
        }
        return $plannings;
    }

    /**
     * Get all observations statistics for a given set of planning and for userid
     *
     * @param array $planningsids
     * @param int $userid
     * @return array
     */
    public static function get_planning_infos(array $planningsids, int $userid) {
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
            $groupstats = self::get_group_infos_for_planning($planningid);
            $category = self::get_category_for_planning_id($planningid);
            $stats[] = [
                'id' => $planning->get('id'),
                'groupstats' => $groupstats,
                'info' => self::get_planning_info($planningid),
                'category' => $category,
                'categorytext' => self::get_category_text_for_planning_id($planningid, $category),
            ];
        }
        return $stats;
    }

    /**
     * Get all observations statistics for a given planning
     *
     * @param int $planningid
     * @return array|null
     */
    public static function get_group_infos_for_planning(int $planningid): ?array {
        $planning = planning::get_record(['id' => $planningid]);
        $stats = ['groupid' => $planning->get('groupid')];
        $students = self::get_students_for_planning_id($planningid);
        $stats['nbstudents'] = count($students);
        return $stats;
    }

    /**
     * Retrieves the users which are students  associated with a given planning ID.
     *
     * @param int $planningid The ID of the planning.
     * @return array An array of users.
     */
    public static function get_students_for_planning_id(int $planningid): array {
        $planning = planning::get_record(['id' => $planningid]);
        $competvet = competvet::get_from_situation_id($planning->get('situationid'));
        $situationcontext = $competvet->get_context();
        $groupmembers = groups_get_members($planning->get('groupid'), 'u.id');
        foreach ($groupmembers as $index => $groupmember) {
            // Check if this user is a student or not.
            if (!utils::is_student($groupmember->id, $situationcontext->id)) {
                unset($groupmembers[$index]);
            }
        }
        return $groupmembers;
    }

    protected static function get_category_for_planning_id(int $planningid): int {
        $planning = planning::get_record(['id' => $planningid]);
        // First check: is this the current week ?
        $now = time();
        if ($now >= $planning->get('startdate') && $now <= $planning->get('enddate')) {
            return planning::CATEGORY_CURRENT;
        }
        if ($now < $planning->get('startdate')) {
            return planning::CATEGORY_FUTURE;
        }
        // Second check: is this a past week and what is the status depending on the completion.
        // TODO this will change depending on the grading strategy and we will only take grade info into account.
        $students = self::get_students_for_planning_id($planningid);
        $nbstudents = count($students);
        $allcompletedobservations = observation::get_records([
            'planningid' => $planningid,
            'status' => observation::STATUS_COMPLETED,
        ]);
        $competvet = competvet::get_from_situation_id($planning->get('situationid'));
        $requiredobservations = $competvet->get_situation()->get('evalnum');
        $studentmembersid = array_fill_keys(array_keys($students), 0);

        foreach ($allcompletedobservations as $completedobservation) {
            $studentid = $completedobservation->get('studentid');
            if (isset($studentmembersid[$studentid])) {
                $studentmembersid[$studentid] += 1;
            }
        }
        $studentfullyassessed = count(array_filter($studentmembersid, fn($count) => $count >= $requiredobservations));
        if ($nbstudents == $studentfullyassessed) {
            return planning::CATEGORY_OBSERVER_COMPLETED;
        }
        return planning::CATEGORY_OBSERVER_LATE;
    }

    /**
     * Get information for planning
     *
     * @param int $planningid
     * @return array
     */
    public static function get_planning_info(int $planningid): array {
        $planning = planning::get_record(['id' => $planningid]);
        $planningarray = (array) $planning->to_record();
        $competvet = competvet::get_from_situation_id($planning->get('situationid'));
        $planningarray = array_intersect_key($planningarray,
            array_fill_keys(['id', 'startdate', 'enddate', 'session', 'groupid', 'situationid'], 0));
        $planningarray['groupname'] = groups_get_group_name($planning->get('groupid'));
        $planningarray['situationname'] = $competvet->get_course_module()->name;
        return $planningarray;
    }

    protected static function get_category_text_for_planning_id(int $planningid, int $category): string {
        return get_string('planningcategory:' . planning::CATEGORY[$category], 'mod_competvet');
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
        $planning = planning::get_record(['id' => $planningid]);
        $situation = situation::get_record(['id' => $planning->get('situationid')]);
        $result =
            [
                'id' => $userid,
                'planningid' => $planningid,
                'info' => self::create_planning_info_for_student($userid, $situation, $observations),
            ];
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
        $info = [];
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
        $info[] = $eval;
        $info[] = $autoeval;
        return $info;
    }

    /**
     * Get users infos for planning id
     *
     * @param int $planningid
     * @return void
     */
    public static function get_users_infos_for_planning_id(int $planningid): array {
        $students = [];
        $planning = planning::get_record(['id' => $planningid]);
        $situation = situation::get_record(['id' => $planning->get('situationid')]);
        $competvet = competvet::get_from_situation_id($planning->get('situationid'));
        $studentsid = array_keys(self::get_students_for_planning_id($planningid));
        if (!has_capability('mod/competvet:viewother', $competvet->get_context())) {
            global $USER;
            if (in_array($USER->id, $studentsid)) {
                $studentsid = [$USER->id];
            } else {
                $studentsid = [];
            }
        }
        foreach ($studentsid as $studentid) {
            $userinfo = [];
            $userinfo['userinfo'] = utils::get_user_info($studentid);
            $userinfo['userinfo']['role'] = 'student';
            $params = ['planningid' => $planningid, 'status' => observation::STATUS_COMPLETED, 'studentid' => $studentid];
            $observations =
                observation::get_records($params, 'studentid, observerid');
            $userinfo['planninginfo'] = self::create_planning_info_for_student($studentid, $situation, $observations);
            $students[] = $userinfo;
        }
        return ['students' => $students, 'observers' => self::get_observers_infos_for_planning_id($planningid)];
    }

    /**
     * Get users infos for planning id
     *
     * @param int $planningid
     * @return void
     */
    public static function get_observers_infos_for_planning_id(int $planningid): array {
        $observers = [];
        $observersid = self::get_observers_for_planning_id($planningid);
        foreach ($observersid as $observerid => $role) {
            $observer = [];
            $observer['userinfo'] = utils::get_user_info($observerid);
            $observer['userinfo']['role'] = $role;
            $observers[] = $observer;
        }
        return $observers;
    }

    /**
     * Retrieves the users which are observers associated with a given planning ID.
     *
     * @param int $planningid The ID of the planning.
     * @return array An array of users where the keys are user IDs and the values are their roles as observers.
     */
    private static function get_observers_for_planning_id(int $planningid): array {
        $planning = planning::get_record(['id' => $planningid]);
        $competvet = competvet::get_from_situation_id($planning->get('situationid'));
        $situationcontext = $competvet->get_context();
        $allenrolled = enrol_get_course_users_roles($situationcontext->get_course_context()->instanceid);
        $observers = [];
        foreach ($allenrolled as $userid => $roles) {
            try {
                $toprole = user_role::get_top($userid, $competvet->get_situation()->get('id'));
                if ($toprole != 'student' && $toprole != 'unknown') {
                    $observers[$userid] = $toprole;
                }
            } catch (\Exception $e) {
                debugging("Roles issue with $userid :" . $e->getMessage());
            }

        }
        return $observers;
    }

    /**
     * Update the planning
     *
     * @param int $planningid - The planning id
     * @param int $situationid - The situation id
     * @param int $groupid - The group id
     * @param string $startdate - The start date
     * @param string $enddate - The end date
     * @param string $groupname - The group name
     * @param string $session - The session name
     * @return void
     */
    public static function update_planning(
        int $planningid,
        int $situationid,
        int $groupid,
        string $startdate,
        string $enddate,
        string $session
    ): void {
        $planning = planning::get_record(['id' => $planningid]);
        if (!$planning) {
            $planning = new planning(0);
        }
        $planning->set('situationid', $situationid);
        $planning->set('groupid', $groupid);
        $planning->set('startdate', strtotime($startdate));
        $planning->set('enddate', strtotime($enddate));
        $planning->set('session', $session);
        if ($planning->get('id')) {
            $planning->update();
        } else {
            $planning->create();
        }
    }

    /**
     * Delete the planning
     * @param int $planningid - The planning id
     */
    public static function delete_planning(int $planningid): void {
        $planning = planning::get_record(['id' => $planningid]);
        if ($planning) {
            $planning->delete();
        }
    }
}
