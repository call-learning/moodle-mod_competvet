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

use core_reportbuilder\local\filters\date;
use mod_competvet\competvet;
use mod_competvet\local\persistent\observation;
use mod_competvet\local\persistent\planning;
use mod_competvet\reportbuilder\local\helpers\data_retriever_helper;
use mod_competvet\reportbuilder\local\systemreports\planning_per_situation;
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
    const PLANNING_FIELDS = [
        'planning:startdateraw' => 'startdate',
        'planning:enddateraw' => 'enddate',
        'planning:session' => 'session',
        'group:name' => 'groupname',
        'planning:groupid' => 'groupid',
        'id' => 'id',
    ];

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
        $parameters = [
            'situationid' => $situationid,
        ];
        $competvet = competvet::get_from_situation_id($situationid);
        if (!$competvet->has_view_access($userid)) {
            return [];
        }
        $situationcontext = $competvet->get_context();

        $filters = null;
        if ($nofuture) {
            $filters = [
                'planning:startdate_operator' => date::DATE_PAST,
                'planning:startdate_value' => null,
                'planning:startdate_unit' => '-1 hour',
            ];
        }
        $allplannings = data_retriever_helper::get_data_from_system_report(
            planning_per_situation::class,
            $situationcontext,
            $parameters,
            $filters,
        );
        $plannings = [];
        $allusergroups = groups_get_all_groups($situationcontext->get_course_context()->instanceid, $userid);
        $allusergroupsid = array_keys($allusergroups);
        $isstudent = utils::is_student($userid, $situationcontext->id);
        foreach ($allplannings as $planning) {
            if ($isstudent && !in_array($planning['planning:groupid'], $allusergroupsid)) {
                // Remove planning for which this user is not involved.
                continue;
            }
            $newplanning = [];
            foreach (self::PLANNING_FIELDS as $originalname => $targetfieldname) {
                $newplanning[$targetfieldname] = $planning[$originalname];
            }
            $plannings[] = $newplanning;
        }
        return $plannings;
    }

    public static function get_user_stats_for_planning(int $planningid, int $userid): ?array {
        $planning = planning::get_record(['id' => $planningid]);
        $competvet = competvet::get_from_situation_id($planning->get('situation'));
        $situationcontext = $competvet->get_context();
        $isstudent = utils::is_student($userid, $situationcontext->id);
        if ($isstudent) {
            return null;
        }
        // $context = \context_system::instance();
        // $allobservations = data_retriever_helper::get_data_from_system_report(
        // observations_per_planning::class,
        // $context,
        // ['onlyforplanningid' => "$planningid",
        // 'onlyforstatus' => join(',', [
        // observation::STATUS_NOTSTARTED, observation::STATUS_INPROGRESS,
        // ]),
        // ]
        // );
        // $requiredevals = $allobservations[0]['situation:evalnum'] ?? 0;

        return ['nbstudentstoeval' => 2, 'nbstudents' => 3];
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
            $studentmembersid[$completedobservation->get('studentid')] += 1;
        }
        $studentfullyassessed = count(array_filter($studentmembersid, fn($count) => $count >= $requiredobservations));
        if ($nbstudents == $studentfullyassessed) {
            return planning::CATEGORY_OBSERVER_COMPLETED;
        }
        return planning::CATEGORY_OBSERVER_LATE;
    }

    protected static function get_category_text_for_planning_id(int $planningid, int $category): string {
        return get_string('planningcategory:' . planning::CATEGORY[$category], 'mod_competvet');
    }

    /**
     * Retrieves the users associated with a given planning ID.
     *
     * @param int $planningid The ID of the planning.
     * @return array An array of users.
     */
    public static function get_users_for_planning_id(int $planningid): array {
        $studentsid = self::get_students_for_planning_id($planningid);
        $students = [];
        foreach ($studentsid as $studentid => $student) {
            $students[] = utils::get_user_info($studentid);
        }
        $observers = [];
        $observersid = self::get_observers_for_planning_id($planningid);
        foreach ($observersid as $observerid => $role) {
            $observer = utils::get_user_info($observerid);
            $observer['rolename'] = $role;
            $observers[] = $observer;
        }
        return ['students' => $students, 'observers' => $observers];
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
            $toprole = user_role::get_top($userid, $competvet->get_situation()->get('id'));
            if ($toprole != 'student' && $toprole != 'unknown') {
                $observers[$userid] = $toprole;
            }
        }
        return $observers;
    }
}