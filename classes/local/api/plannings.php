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
use mod_competvet\local\persistent\case_entry;
use mod_competvet\local\persistent\cert_decl;
use mod_competvet\local\persistent\form;
use mod_competvet\local\persistent\observation;
use mod_competvet\local\persistent\grade;
use mod_competvet\local\persistent\planning;
use mod_competvet\local\persistent\planning_pause;
use mod_competvet\local\persistent\situation;
use mod_competvet\local\persistent\todo;
use mod_competvet\utils;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->dirroot . '/group/lib.php');

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
            if (empty($allusergroupsid)) {
                return [];
            }
            [$sql, $params] = $DB->get_in_or_equal($allusergroupsid, SQL_PARAMS_NAMED, 'allusergroupsid');
            $planninngssql .= " AND groupid $sql";
            $planningfilters = array_merge($planningfilters, $params);
        }
        if ($nofuture) {
            $planningfilters['minstartdate'] = (new \DateTime('next Monday'))->getTimestamp();
            $planninngssql .= " AND startdate < :minstartdate";
        }
        $allplannings = planning::get_records_select($planninngssql, $planningfilters, 'startdate ASC');
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
            $planninginfo = self::get_planning_info($planningid);
            if (!$planninginfo) {
                continue;
            }
            $groupstats = self::get_group_infos_for_planning($planningid);
            $category = self::get_category_for_planning_id($planningid);
            $stats[] = [
                'id' => $planning->get('id'),
                'groupstats' => $groupstats,
                'info' => $planninginfo,
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
            } else {
                $groupmembers[$index]->type = 'groupmember';
            }
        }
        $orphanes = self::check_orphaned_users($planningid, $groupmembers);
        // Add orphanes to the groupmembers.
        foreach ($orphanes as $orphan) {
            if (!isset($groupmembers[$orphan])) {
                $groupmembers[$orphan] = new \stdClass();
                $groupmembers[$orphan]->id = $orphan;
                $groupmembers[$orphan]->type = 'orphan';
                $groupmembers[$orphan]->solution = self::find_fixes_for_orphan($orphan, $planningid);
            }
        }
        return $groupmembers;
    }

    /**
     * Check if there are orphaned users in the planning.
     * Orphaned users are users who are not in the group of the planning.
     * But have data in observations, certifications or case entries.
     * @param int $planningid
     * @param array $groupmembers
     * @return array
     */
    private static function check_orphaned_users(int $planningid, array $groupmembers): array {
        $orphanes = [];
        if (!is_siteadmin()) {
            return $orphanes;
        }
        $students = array_map(fn($user) => $user->id, $groupmembers);
        $allobservations = observation::get_records(['planningid' => $planningid]);
        foreach ($allobservations as $observation) {
            if (!in_array($observation->get('studentid'), $students)) {
                $orphanes[] = $observation->get('studentid');
            }
        }
        $grades = grade::get_records(['planningid' => $planningid]);
        foreach ($grades as $grade) {
            if (!in_array($grade->get('studentid'), $students)) {
                $orphanes[] = $grade->get('studentid');
            }
        }
        $caseentries = case_entry::get_records(['planningid' => $planningid]);
        foreach ($caseentries as $caseentry) {
            if (!in_array($caseentry->get('studentid'), $students)) {
                $orphanes[] = $caseentry->get('studentid');
            }
        }
        // Make sure we have unique values.
        $orphanes = array_unique($orphanes);
        return $orphanes;
    }

    /**
     * Find a fix for an orphaned user.
     * Finds if the user is in 1 other group in the same situation, then allows to move the user to this group.
     * Finds if the user is not in any group in the same situation, then allows to add the user back to the original group from this
     * planning.
     * If user is in multiple groups, then we need to ask to update the user manually in the course group settings.
     * @param int $userid
     * @param int $planningid
     */
    private static function find_fixes_for_orphan(int $userid, int $planningid): array {
        $planning = planning::get_record(['id' => $planningid]);
        $plannings = planning::get_records(['situationid' => $planning->get('situationid')]);
        foreach($plannings as $p) {
            if ($p->get('id') == $planningid) {
                continue;
            }
            $groupmembers = groups_get_members($p->get('groupid'), 'u.id');
            foreach ($groupmembers as $groupmember) {
                if ($groupmember->id == $userid) {
                    $groupname = groups_get_group_name($p->get('groupid'));
                    return [
                        'action' => 'orphanfix:move',
                        'fixstring' => get_string('orphanfix:move', 'mod_competvet', $groupname),
                        'userid' => $userid,
                        'groupid' => $p->get('groupid'),
                        'groupname' => $groupname,
                        'oldplanningid' => $planningid,
                        'planningid' => $p->get('id'),
                    ];
                }
            }
        }
        $groupname = groups_get_group_name($planning->get('groupid'));
        return [
            'action' => 'orphanfix:add',
            'fixstring' => get_string('orphanfix:add', 'mod_competvet', $groupname),
            'userid' => $userid,
            'groupid' => $planning->get('groupid'),
            'groupname' => $groupname,
            'oldplanningid' => $planningid,
            'planningid' => $planning->get('id'),
        ];
    }

    /**
     * Fix orphaned user in the planning.
     * @param int $userid
     * @param int $planningid
     * @param int $groupid
     * @param string $action
     * @return string
     */
    public static function fix_orphan_user(int $userid, int $groupid, int $planningid, int $oldplanningid, string $action): string {
        if ($action == 'orphanfix:move') {
            // Move user cases to the new planning.
            $cases = case_entry::get_records(['planningid' => $oldplanningid, 'studentid' => $userid]);
            foreach ($cases as $case) {
                $case->set('planningid', $planningid);
                $case->save();
            }
            $observations = observation::get_records(['planningid' => $oldplanningid, 'studentid' => $userid]);
            foreach ($observations as $observation) {
                $observation->set('planningid', $planningid);
                $observation->save();
            }
            $grades = grade::get_records(['planningid' => $oldplanningid, 'studentid' => $userid]);
            foreach ($grades as $grade) {
                $grade->set('planningid', $planningid);
                $grade->save();
            }
            $todos = todo::get_records(['planningid' => $oldplanningid, 'userid' => $userid]);
            foreach ($todos as $todo) {
                $todo->set('planningid', $planningid);
                $todo->save();
            }
            $certdecl = cert_decl::get_records(['planningid' => $oldplanningid, 'studentid' => $userid]);
            foreach ($certdecl as $cert) {
                $cert->set('planningid', $planningid);
                $cert->save();
            }
            $form = form::get_records(['planningid' => $oldplanningid, 'userid' => $userid]);
            foreach ($form as $f) {
                $f->set('planningid', $planningid);
                $f->save();
            }
            return "Orphaned user $userid moved from planning $oldplanningid to planning $planningid";
        }
        if ($action == 'orphanfix:add') {
            groups_add_member($groupid, $userid);
            return "Orphaned user $userid add to group $groupid";
        }
        return '';
    }

    /**
     * Get category for planning id
     *
     * @param int $planningid
     * @return int
     */
    public static function get_category_for_planning_id(int $planningid): int {
        $planning = planning::get_record(['id' => $planningid]);
        // First check: is this the current week ?
        $now = time();
        if ($now >= $planning->get('startdate') && $now <= $planning->get('enddate')) {
            // Check if the planning is paused.
            if (self::is_planning_paused($planningid)) {
                return planning::CATEGORY_PAUSED;
            }
            return planning::CATEGORY_CURRENT;
        }
        if ($now < $planning->get('startdate')) {
            return planning::CATEGORY_FUTURE;
        }
        // Second check: is this a past week and what is the status depending on the completion.
        // TODO: MDL-000 this will change depending on the grading strategy and we will only take grade info into account.
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
            $return = planning::CATEGORY_OBSERVER_COMPLETED;
        }
        return planning::CATEGORY_OBSERVER_LATE;
    }

    /**
     * Get information for planning
     *
     * @param int $planningid
     * @return array|null
     */
    public static function get_planning_info(int $planningid): ?array {
        $planning = planning::get_record(['id' => $planningid]);
        if (!$planning) {
            return null;
        }
        $planningarray = (array) $planning->to_record();
        $competvet = competvet::get_from_situation_id($planning->get('situationid'));
        $planningarray = array_intersect_key(
            $planningarray,
            array_fill_keys(['id', 'startdate', 'enddate', 'session', 'groupid', 'situationid'], 0)
        );
        $planningarray['groupname'] = groups_get_group_name($planning->get('groupid'));
        $planningarray['situationname'] = $competvet->get_course_module()->name;
        $planningarray['cmid'] = $competvet->get_course_module()->id;
        return $planningarray;
    }

    /**
     * Get category text for planning id
     *
     * @param int $planningid
     * @param int $category
     * @return string
     */
    public static function get_category_text_for_planning_id(int $planningid, int $category): string {
        return get_string('planningcategory:' . planning::CATEGORY[$category], 'mod_competvet');
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
            $userinfo['planninginfo'] = self::get_planning_stats_for_student($planningid, $studentid);
            $students[] = $userinfo;
        }
        return ['students' => $students, 'observers' => self::get_observers_infos_for_planning_id($planningid)];
    }

    /**
     * Get planning info for student
     *
     * @param int $planningid
     * @param int $userid
     * @param bool|null $associative
     * @return array
     */
    public static function get_planning_stats_for_student(int $planningid, int $userid, ?bool $associative = false): array {
        $planning = planning::get_record(['id' => $planningid]);
        $situation = $planning->get_situation();
        $result =
            [
                'id' => $userid,
                'planningid' => $planningid,
                'situationid' => $situation->get('id'),
                'stats' => self::create_planning_stats_for_student($userid, $planningid),
            ];
        if (!$associative) {
            $result['stats'] = array_values($result['stats']);
        }
        return $result;
    }

    /**
     * Creates planning information (stats) for a student.
     *
     * @param int $studentid The ID of the student.
     * @param int $planningid The ID of the planning.
     * @return array The planning information for the student.
     */
    protected static function create_planning_stats_for_student(int $studentid, int $planningid) {
        $planning = planning::get_record(['id' => $planningid]);
        $situation = $planning->get_situation();
        $observations =
            observation::get_records(['planningid' => $planningid, 'studentid' => $studentid], 'studentid, observerid');

        $gridid = criteria::get_grid_for_planning($planningid, 'cert')->get('id');
        $criteria = criteria::get_sorted_parent_criteria($gridid);
        $certifcations = certifications::get_certifications($planningid, $studentid);
        $numvalidated = array_reduce($certifcations, fn($carry, $certification) => $carry + $certification['confirmed'], 0);
        $entries = case_entry::get_records(['studentid' => $studentid, 'planningid' => $planningid]);

        $info = [];
        // New structure.
        $info['eval'] = [
            'type' => 'eval',
            'nbdone' => 0,
            'nbrequired' => $situation->get('evalnum'),
            'pass' => 0,
        ];
        $info['autoeval'] = [
            'type' => 'autoeval',
            'nbdone' => 0,
            'nbrequired' => $situation->get('autoevalnum'),
            'pass' => 0,
        ];
        $info['cert'] = [
            'type' => 'cert',
            'nbdone' => $numvalidated,
            // Change here, we consider all the criteria in the stats even if we need only certpnum / 100 * count($criteria).
            'nbrequired' => count($criteria), // TODO: MDL-000 this is not really nb required so we might change the wording here.
            'pass' => 0,
        ];
        $info['list'] = [
            'type' => 'list',
            'nbdone' => count($entries),
            'nbrequired' => $situation->get('casenum'),
            'pass' => 0,
        ];

        foreach ($observations as $observation) {
            if ($observation->get('studentid') != $studentid) {
                continue;
            }
            if ($observation->get_observation_type() == observation::CATEGORY_EVAL_AUTOEVAL) {
                $info['autoeval']['nbdone']++;
            } else {
                $info['eval']['nbdone']++;
            }
        }

        // Set the pass to 1 if nbdone >= nbrequired.
        foreach ($info as $type => $data) {
            if ($type == 'cert') {
                $info[$type]['pass'] = $data['nbdone'] >= round(count($criteria) * $situation->get('certifpnum') / 100) ? 1 : 0;
            } else {
                $info[$type]['pass'] = $data['nbdone'] >= $data['nbrequired'] ? 1 : 0;
            }
        }
        if (!$situation->get('haseval')) {
            unset($info['eval']);
        }
        if (!$situation->get('hascertif')) {
            unset($info['cert']);
        }
        if (!$situation->get('hascase')) {
            unset($info['list']);
        }
        return $info;
    }

    /**
     * Get users infos for planning id
     *
     * @param int $planningid
     * @return array
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
     *
     * @param int $planningid - The planning id
     */
    public static function delete_planning(int $planningid): void {
        $planning = planning::get_record(['id' => $planningid]);
        if ($planning) {
            $planning->delete();
        }
    }

    /**
     * Get students info for planning id
     *
     * @param int $planningid
     * @return array|array[]
     */
    public static function get_students_info_for_planning_id(int $planningid) {
        $users = static::get_students_for_planning_id($planningid);
        return array_map(fn($user) => utils::get_user_info($user->id), $users);
    }

    /**
     * Return true if the planning has user data (observations, evaluations, etc.)
     *
     * @param int $planningid
     * @return bool
     */
    public static function has_user_data(int $planningid): bool {
        $hasobservations = observation::count_records(['planningid' => $planningid]) > 0;
        $hascases = case_entry::count_records(['planningid' => $planningid]) > 0;
        $hascertifications = cert_decl::count_records(['planningid' => $planningid]) > 0;
        return $hasobservations || $hascases || $hascertifications;
    }

    /**
     * Get planning pauses for a given planning ID
     *
     * @param int $planningid The ID of the planning.
     * @return array An array of planning pauses.
     */
    public static function get_planning_pauses(int $planningid): array {
        $pauses = planning_pause::get_records(['planningid' => $planningid]);
        $pauseinfo = [];
        foreach ($pauses as $pause) {
            $pauseinfo[] = [
                'id' => $pause->get('id'),
                'planningid' => $pause->get('planningid'),
                'startdate' => userdate($pause->get('startdate'), '%Y-%m-%dT%H:%M'),
                'startdatets' => $pause->get('startdate'),
                'enddate' => userdate($pause->get('enddate'), '%Y-%m-%dT%H:%M'),
                'enddatets' => $pause->get('enddate'),
                'usermodified' => $pause->get('usermodified'),
                'timecreated' => $pause->get('timecreated'),
                'timemodified' => $pause->get('timemodified'),
            ];
        }
        return $pauseinfo;
    }

    /**
     * Delete a pause by its ID.
     *
     * @param int $pauseid The ID of the pause to delete.
     * @return bool True if the pause was deleted, false otherwise.
     */
    public static function delete_pause(int $pauseid): bool {
        $pause = new planning_pause($pauseid);
        if (!$pause->get('id')) {
            return false;
        }
        return $pause->delete();
    }

    /**
     * Update or insert a pause.
     *
     * @param int $pauseid The ID of the pause.
     * @param int $planningid The ID of the planning.
     * @param string $startdate The start date of the pause.
     * @param string $enddate The end date of the pause.
     * @return planning_pause The pause object.
     */
    public static function update_pause(int $pauseid, int $planningid, string $startdate, string $enddate): planning_pause {
        $data = [
            'planningid' => $planningid,
            'startdate' => strtotime($startdate),
            'enddate' => strtotime($enddate),
        ];

        if ($pauseid) {
            $pause = new planning_pause($pauseid);
            $pause->set('startdate', $data['startdate']);
            $pause->set('enddate', $data['enddate']);
            $pause->update();
        } else {
            $pause = new planning_pause(0, (object) $data);
            $pause->create();
        }
        return $pause;
    }

    /**
     * Check if the planning is paused
     *
     * @param int $planningid The ID of the planning.
     * @return bool True if the planning is paused, false otherwise.
     */
    public static function is_planning_paused(int $planningid): bool {
        $now = time();
        $pauses = planning_pause::get_records(['planningid' => $planningid]);
        foreach ($pauses as $pause) {
            if ($now >= $pause->get('startdate') && $now <= $pause->get('enddate')) {
                return true;
            }
        }
        return false;
    }
}
