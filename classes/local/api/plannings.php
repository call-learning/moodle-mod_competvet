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

use core_date;
use mod_competvet\competvet;
use mod_competvet\local\persistent\case_entry;
use mod_competvet\local\persistent\cert_decl;
use mod_competvet\local\persistent\grade;
use mod_competvet\local\persistent\group_history;
use mod_competvet\local\persistent\observation;
use mod_competvet\local\persistent\planning;
use mod_competvet\local\persistent\planning_pause;
use mod_competvet\local\persistent\situation;
use mod_competvet\utils;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/gradelib.php');

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
    const API_PLANNING_FIELDS = [
        'id', 'situationid', 'startdate', 'enddate', 'session', 'session',
        'groupid', 'groupname', 'historical', 'readonly',
    ];

    /**
     * Resolve historical metadata for a planning.
     *
     * Returns an array with:
     *  - historical: bool - true if the planning's group no longer exists
     *  - readonly: bool - true if the planning is historical (and thus read-only)
     *  - groupname: string - current group name, preserved history name, or fallback
     *
     * @param int $planningid The planning ID.
     * @return array The metadata array.
     */
    public static function resolve_planning_metadata(int $planningid): array {
        global $DB;
        $planning = planning::get_record(['id' => $planningid]);
        if (!$planning) {
            return ['historical' => false, 'readonly' => false, 'groupname' => ''];
        }

        $groupid = $planning->get('groupid');
        $situation = $planning->get_situation();
        $competvet = competvet::get_from_situation_id($situation->get('id'));
        $courseid = $competvet->get_course_module()->course;

        // Check if the group exists within the planning's course context.
        $groupexists = $DB->record_exists('groups', ['id' => $groupid]);

        if ($groupexists) {
            // Normal planning: use the current group name.
            return [
                'historical' => false,
                'readonly' => false,
                'groupname' => groups_get_group_name($groupid),
            ];
        }

        // Historical planning: look for preserved group name.
        $historicalname = group_history::get_group_name_for_planning($planningid);
        $groupname = $historicalname !== null
            ? $historicalname
            : get_string('historicalgroupunknown', 'mod_competvet', $groupid);

        return [
            'historical' => true,
            'readonly' => true,
            'groupname' => $groupname,
        ];
    }

    /**
     * Get planning for a given situation ID
     *
     * @param int $situationid situation ID
     * @param int $userid user ID
     * @param bool $nofuture do not show future situation
     * @param bool $viewall if true, return plannings for all groups even when the user is a student
     * @return array array of plannings
     */
    public static function get_plannings_for_situation_id(
        int $situationid,
        int $userid,
        bool $nofuture = true,
        bool $viewall = false
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
        $planninngsql = 'situationid = :situationid';
        $allusergroupsid = [];
        if ($isstudent && !$viewall) {
            global $DB;
            // For historical plannings, include them even if the student is not in any group.
            // For normal plannings, filter by group membership.
            $allusergroups = groups_get_all_groups($situationcontext->get_course_context()->instanceid, $userid);
            $allusergroupsid = array_keys($allusergroups);
            if (empty($allusergroupsid)) {
                // No groups - only return historical plannings (where group doesn't exist).
                // We'll filter these out later after fetching all plannings.
                $planninngsql .= ' AND 1=1';
            } else {
                [$sql, $params] = $DB->get_in_or_equal($allusergroupsid, SQL_PARAMS_NAMED, 'allusergroupsid');
                $planninngsql .= " AND groupid $sql";
                $planningfilters = array_merge($planningfilters, $params);
            }
        }
        if ($nofuture) {
            $clock = \core\di::get(\core\clock::class);
            $nextmonday = $clock->now();
            $nextmonday = $nextmonday->modify('next Monday');
            $planningfilters['minstartdate'] = $nextmonday->getTimestamp();
            $planninngsql .= " AND startdate < :minstartdate";
        }
        $allplannings = planning::get_records_select($planninngsql, $planningfilters, 'startdate ASC');
        $plannings = [];
        foreach ($allplannings as $planning) {
            $newplanning = (array) $planning->to_record();
            $metadata = self::resolve_planning_metadata($planning->get('id'));
            $newplanning['groupname'] = $metadata['groupname'];
            $newplanning['historical'] = $metadata['historical'];
            $newplanning['readonly'] = $metadata['readonly'];

            // When the student is not in any group, only return historical plannings.
            if ($isstudent && !$viewall && empty($allusergroupsid) && !$metadata['historical']) {
                continue;
            }

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
     * For normal plannings, uses Moodle group membership.
     * For historical plannings (group deleted), derives participants from CompetVet records.
     *
     * @param int $planningid The ID of the planning.
     * @return array An array of users.
     */
    public static function get_students_for_planning_id(int $planningid): array {
        $planning = planning::get_record(['id' => $planningid]);
        if (!$planning) {
            return [];
        }

        $metadata = self::resolve_planning_metadata($planningid);

        if (!$metadata['historical']) {
            // Normal planning: use Moodle group membership.
            return self::get_students_from_group_membership($planningid);
        }

        // Historical planning: derive from CompetVet records.
        return self::get_students_from_records($planningid);
    }

    /**
     * Get students from Moodle group membership (normal planning path).
     *
     * @param int $planningid The planning ID.
     * @return array An array of user objects.
     */
    protected static function get_students_from_group_membership(int $planningid): array {
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

    /**
     * Get students from CompetVet records (historical planning path).
     *
     * Collects distinct student IDs from observations, certifications, cases,
     * and grades attached to the planning, then loads user objects.
     *
     * @param int $planningid The planning ID.
     * @return array An array of user objects.
     */
    protected static function get_students_from_records(int $planningid): array {
        global $DB;

        // Collect distinct student IDs from all planning-scoped CompetVet records.
        $sql = "SELECT DISTINCT o.studentid
                  FROM {competvet_observation} o
                 WHERE o.planningid = :planningid
                UNION
                SELECT DISTINCT cd.studentid
                  FROM {competvet_cert_decl} cd
                 WHERE cd.planningid = :planningid2
                UNION
                SELECT DISTINCT ce.studentid
                  FROM {competvet_case_entry} ce
                 WHERE ce.planningid = :planningid3";

        $params = [
            'planningid' => $planningid,
            'planningid2' => $planningid,
            'planningid3' => $planningid,
        ];

        $studentids = $DB->get_fieldset_sql($sql, $params);
        if (empty($studentids)) {
            return [];
        }

        [$insql, $inparams] = $DB->get_in_or_equal($studentids, SQL_PARAMS_NAMED, 'sid');
        $sql = "SELECT u.*
                  FROM {user} u
                 WHERE u.id $insql
                   AND u.deleted = 0
              ORDER BY u.lastname, u.firstname";

        return $DB->get_records_sql($sql, $inparams);
    }

    /**
     * Get orphaned students for a given planning ID.
     *
     * An orphaned student is a user who has data attached to a planning
     * (observations, certifications, cases or grades) but who is not a regular
     * student of the planning's group: either they are no longer a member of the
     * planning's group (their group assignment changed) or they no longer have
     * the student role in the course (their role was changed).
     *
     * @param int $planningid The ID of the planning.
     * @return array An array of orphaned user objects, keyed by user ID.
     */
    public static function get_orphaned_students_for_planning_id(int $planningid): array {
        $planning = planning::get_record(['id' => $planningid]);
        if (!$planning) {
            return [];
        }

        // Historical plannings already derive their students from the records, so there are no orphans.
        $metadata = self::resolve_planning_metadata($planningid);
        if ($metadata['historical']) {
            return [];
        }

        global $DB;
        $groupid = $planning->get('groupid');
        $competvet = competvet::get_from_situation_id($planning->get('situationid'));
        $contextid = $competvet->get_context()->id;

        // Collect distinct student IDs from all planning-scoped CompetVet records.
        $sql = "SELECT DISTINCT o.studentid
                  FROM {competvet_observation} o
                 WHERE o.planningid = :planningid
                UNION
                SELECT DISTINCT cd.studentid
                  FROM {competvet_cert_decl} cd
                 WHERE cd.planningid = :planningid2
                UNION
                SELECT DISTINCT ce.studentid
                  FROM {competvet_case_entry} ce
                 WHERE ce.planningid = :planningid3
                UNION
                 SELECT DISTINCT g.studentid
                   FROM {competvet_grades} g
                  WHERE g.planningid = :planningid4";

        $params = [
            'planningid' => $planningid,
            'planningid2' => $planningid,
            'planningid3' => $planningid,
            'planningid4' => $planningid,
        ];

        $studentids = $DB->get_fieldset_sql($sql, $params);
        if (empty($studentids)) {
            return [];
        }

        // Orphaned users are users with records on the planning who are no longer a regular
        // student of the planning group: either they left the group (group assignment changed)
        // or they lost the student role (role changed).
        $orphanids = array_filter($studentids, fn($studentid) =>
            !groups_is_member($groupid, $studentid) || !utils::is_student($studentid, $contextid));
        if (empty($orphanids)) {
            return [];
        }

        [$insql, $inparams] = $DB->get_in_or_equal($orphanids, SQL_PARAMS_NAMED, 'sid');
        $sql = "SELECT u.*
                  FROM {user} u
                 WHERE u.id $insql
                   AND u.deleted = 0
               ORDER BY u.lastname, u.firstname";

        return $DB->get_records_sql($sql, $inparams);
    }

    /**
     * Find a fix proposal for an orphaned user.
     *
     * If the user is a member of another group whose planning covers the same
     * week in the same situation, propose to move the orphaned records to that
     * planning. Otherwise, propose to add the user back to the original
     * planning group.
     *
     * If the user is still a member of the planning's own group (their role was
     * changed rather than their group), the issue cannot be fixed and an empty
     * array is returned.
     *
     * @param int $userid The orphaned user ID.
     * @param int $planningid The planning ID holding the orphaned records.
     * @return array The fix proposal, or an empty array when the issue cannot be fixed.
     */
    public static function find_orphan_fix(int $userid, int $planningid): array {
        $planning = planning::get_record(['id' => $planningid]);
        $plannings = planning::get_records(['situationid' => $planning->get('situationid')], 'startdate');
        foreach ($plannings as $otherplanning) {
            if ($otherplanning->get('id') == $planningid) {
                continue;
            }
            // Only consider plannings covering the same week as the orphaned planning.
            if (
                $otherplanning->get('startdate') != $planning->get('startdate') ||
                    $otherplanning->get('enddate') != $planning->get('enddate')
            ) {
                continue;
            }
            if (groups_is_member($otherplanning->get('groupid'), $userid)) {
                $groupid = $otherplanning->get('groupid');
                $groupname = groups_get_group_name($groupid);
                return [
                    'action' => 'orphanfix:move',
                    'fixstring' => get_string('orphanfix:move', 'competvet', $groupname),
                    'userid' => $userid,
                    'groupid' => $groupid,
                    'groupname' => $groupname,
                    'oldplanningid' => $planningid,
                    'planningid' => $otherplanning->get('id'),
                ];
            }
        }

        $groupid = $planning->get('groupid');
        // If the user is still a member of the planning's own group, they were not removed from
        // the group (their role was changed instead): there is nothing we can fix, so no fix action.
        if (groups_is_member($groupid, $userid)) {
            return [];
        }
        $groupname = groups_get_group_name($groupid);
        return [
            'action' => 'orphanfix:add',
            'fixstring' => get_string('orphanfix:add', 'competvet', $groupname),
            'userid' => $userid,
            'groupid' => $groupid,
            'groupname' => $groupname,
            'oldplanningid' => $planningid,
            'planningid' => $planningid,
        ];
    }

    /**
     * Fix an orphaned user in a planning.
     *
     * Supported actions:
     * - orphanfix:add: re-add the user to the original planning group.
     * - orphanfix:move: move all orphaned records to the planning of the user's
     *   current group covering the same week in the same situation.
     *
     * @param int $userid The orphaned user ID.
     * @param int $groupid The target group ID.
     * @param int $planningid The target planning ID.
     * @param int $oldplanningid The planning ID holding the orphaned records.
     * @param string $action The fix action.
     * @return string A human readable result message.
     * @throws \moodle_exception If the fix is not valid.
     */
    public static function fix_orphan_user(int $userid, int $groupid, int $planningid, int $oldplanningid, string $action): string {
        if ($action == 'orphanfix:move') {
            $oldplanning = planning::get_record(['id' => $oldplanningid]);
            $targetplanning = planning::get_record(['id' => $planningid]);
            if (!$oldplanning || !$targetplanning || $targetplanning->get('id') == $oldplanningid) {
                throw new \moodle_exception('invaliddata', 'competvet', '', 'planningid');
            }
            // Validate the target planning: same situation, same week.
            if (
                $targetplanning->get('situationid') != $oldplanning->get('situationid') ||
                    $targetplanning->get('startdate') != $oldplanning->get('startdate') ||
                    $targetplanning->get('enddate') != $oldplanning->get('enddate')
            ) {
                throw new \moodle_exception('invaliddata', 'competvet', '', 'planningid');
            }
            // The user must be a member of the target planning group.
            if (!$groupid || $targetplanning->get('groupid') != $groupid || !groups_is_member($groupid, $userid)) {
                throw new \moodle_exception('invaliddata', 'competvet', '', 'groupid');
            }
            self::move_orphan_records($oldplanningid, $planningid, $userid);
            $groupname = groups_get_group_name($groupid);
            return get_string('orphanfixed:move', 'competvet', $groupname);
        }

        if ($action == 'orphanfix:add') {
            $planning = planning::get_record(['id' => $oldplanningid]);
            if (!$planning || !$groupid || $planning->get('groupid') != $groupid) {
                throw new \moodle_exception('invaliddata', 'competvet', '', 'groupid');
            }
            groups_add_member($groupid, $userid);
            $groupname = groups_get_group_name($groupid);
            return get_string('orphanfixed:add', 'competvet', $groupname);
        }

        throw new \moodle_exception('invaliddata', 'competvet', '', 'action');
    }

    /**
     * Move the orphaned records of a user from one planning to another.
     *
     * Only the planning-scoped records attached to the student are moved:
     * observations, certifications, case entries and grades.
     *
     * @param int $oldplanningid The planning ID holding the orphaned records.
     * @param int $newplanningid The target planning ID.
     * @param int $userid The user ID.
     * @return void
     */
    protected static function move_orphan_records(int $oldplanningid, int $newplanningid, int $userid): void {
        $observations = observation::get_records(['planningid' => $oldplanningid, 'studentid' => $userid]);
        foreach ($observations as $observation) {
            $observation->set('planningid', $newplanningid);
            $observation->save();
        }
        $grades = grade::get_records(['planningid' => $oldplanningid, 'studentid' => $userid]);
        foreach ($grades as $grade) {
            $grade->set('planningid', $newplanningid);
            $grade->save();
        }
        $certdecl = cert_decl::get_records(['planningid' => $oldplanningid, 'studentid' => $userid]);
        foreach ($certdecl as $cert) {
            $cert->set('planningid', $newplanningid);
            $cert->save();
        }
        $cases = case_entry::get_records(['planningid' => $oldplanningid, 'studentid' => $userid]);
        foreach ($cases as $case) {
            $case->set('planningid', $newplanningid);
            $case->save();
        }
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
        $clock = \core\di::get(\core\clock::class);
        $now = $clock->time();
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
        $metadata = self::resolve_planning_metadata($planningid);
        $planningarray['groupname'] = $metadata['groupname'];
        $planningarray['historical'] = $metadata['historical'];
        $planningarray['readonly'] = $metadata['readonly'];
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
        // Guard: reject writes to historical plannings.
        self::check_write_allowed($planningid);

        $startdatets = strtotime($startdate);
        $enddatets = strtotime($enddate);

        // Find an existing planning matching the DB unique key to keep this operation idempotent.
        $existingplanning = planning::get_record([
            'situationid' => $situationid,
            'groupid' => $groupid,
            'startdate' => $startdatets,
            'enddate' => $enddatets,
            'session' => $session,
        ]);
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
        // Guard: reject writes to historical plannings.
        self::check_write_allowed($planningid);

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
     * Return true if any planning of the situation already has user data.
     *
     * @param int $situationid
     * @return bool
     */
    public static function situation_has_user_data(int $situationid): bool {
        $plannings = planning::get_records(['situationid' => $situationid]);
        foreach ($plannings as $planning) {
            if (static::has_user_data($planning->get('id'))) {
                return true;
            }
        }
        return false;
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
        $timezone = core_date::get_user_timezone_object();
        foreach ($pauses as $pause) {
            $pauseinfo[] = [
                'id' => $pause->get('id'),
                'planningid' => $pause->get('planningid'),
                // Make sure that the format of the date is compatible with the HTML input type datetime-local
                // and that it is displayed in the user's timezone.
                'startdate' => userdate($pause->get('startdate'), '%Y-%m-%dT%H:%M', $timezone, false),
                'startdatets' => $pause->get('startdate'),
                'enddate' => userdate($pause->get('enddate'), '%Y-%m-%dT%H:%M', $timezone, false),
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
        $clock = \core\di::get(\core\clock::class);
        $now = $clock->time();
        $pauses = planning_pause::get_records(['planningid' => $planningid]);
        foreach ($pauses as $pause) {
            if ($now >= $pause->get('startdate') && $now <= $pause->get('enddate')) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if a planning is historical (group deleted) and throw an error if so.
     *
     * This guard should be called before any planning-scoped mutation
     * (planning updates, evaluation CRUD, certification, cases, forms, deletions).
     *
     * @param int $planningid The planning ID to check.
     * @return void
     * @throws \moodle_exception If the planning is historical and write is not allowed.
     */
    public static function check_write_allowed(int $planningid): void {
        $metadata = self::resolve_planning_metadata($planningid);
        if ($metadata['historical']) {
            throw new \moodle_exception(
                'historicalplanningreadonly',
                'mod_competvet',
                '',
                get_string('historicalplanningreadonly', 'mod_competvet')
            );
        }
    }

    /**
     * Detect plannings whose referenced Moodle groups no longer exist.
     *
     * Returns an array of objects with missing-group information.
     *
     * @param int|null $situationid Optional filter by situation ID.
     * @param int|null $planningid  Optional filter by planning ID.
     * @return array Array of objects with missing-group details.
     */
    public static function detect_missing_groups(?int $situationid = null, ?int $planningid = null): array {
        global $DB;

        // Build the planning filter.
        $planningfilter = [];
        if ($situationid !== null) {
            $planningfilter['situationid'] = $situationid;
        }
        if ($planningid !== null) {
            $planningfilter['id'] = $planningid;
        }

        $plannings = empty($planningfilter) ? planning::get_records() : planning::get_records($planningfilter);

        $missing = [];
        foreach ($plannings as $planning) {
            $groupid = $planning->get('groupid');
            if (empty($groupid)) {
                continue;
            }

            $situation = situation::get_record(['id' => $planning->get('situationid')]);
            if (!$situation) {
                continue;
            }
            $competvet = competvet::get_from_situation($situation);
            $courseid = $competvet->get_course_module()->course;

            // Check if the group exists within the planning's course context.
            $groupexists = groups_group_exists($groupid);

            if ($groupexists) {
                continue;
            }

            // Group is missing. Check for history.
            $hashistory = group_history::has_history_for_planning($planning->get('id'));
            $historyname = $hashistory ? group_history::get_group_name_for_planning($planning->get('id')) : null;

            $missing[] = (object) [
                'planningid' => $planning->get('id'),
                'situationid' => $planning->get('situationid'),
                'situationname' => $situation->get('shortname'),
                'groupid' => $groupid,
                'groupname' => groups_get_group_name($groupid),
                'session' => $planning->get('session'),
                'startdate' => $planning->get('startdate'),
                'enddate' => $planning->get('enddate'),
                'history_present' => $hashistory,
                'history_name' => $historyname,
            ];
        }

        return $missing;
    }

    /**
     * Import group-history metadata for a planning.
     *
     * Returns an array of result objects with status, planningid, groupname, and message.
     *
     * @param array $rows Array of [planningid, groupname] pairs.
     * @param bool $dryrun If true, only preview without writing.
     * @return array Array of result objects.
     */
    public static function import_group_history(array $rows, bool $dryrun = false): array {
        $results = [];

        foreach ($rows as $row) {
            [$planningid, $groupname] = $row;
            $planningid = (int) $planningid;

            // Validate planning exists.
            $planning = planning::get_record(['id' => $planningid]);
            if (!$planning) {
                $results[] = (object) [
                    'status' => 'error',
                    'planningid' => $planningid,
                    'groupname' => $groupname,
                    'message' => "Planning {$planningid} does not exist.",
                ];
                continue;
            }

            // Idempotent upsert.
            $existing = group_history::get_for_planning($planningid);
            if ($existing) {
                $results[] = (object) [
                    'status' => 'duplicate',
                    'planningid' => $planningid,
                    'groupname' => $groupname,
                    'message' => "History already exists for planning {$planningid}.",
                ];
                continue;
            }

            if ($dryrun) {
                $results[] = (object) [
                    'status' => 'dryrun',
                    'planningid' => $planningid,
                    'groupname' => $groupname,
                    'message' => "Would create history for planning {$planningid}, name '{$groupname}'.",
                ];
            } else {
                $history = new group_history(0, (object) [
                    'planningid' => $planningid,
                    'groupname' => $groupname,
                    'timecreated' => time(),
                    'timemodified' => time(),
                ]);
                $history->create();
                $results[] = (object) [
                    'status' => 'created',
                    'planningid' => $planningid,
                    'groupname' => $groupname,
                    'message' => "Created history for planning {$planningid}, name '{$groupname}'.",
                ];
            }
        }

        return $results;
    }
}
