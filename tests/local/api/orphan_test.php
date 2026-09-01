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

use advanced_testcase;
use core_user;
use mod_competvet\competvet;
use mod_competvet\local\persistent\observation;
use mod_competvet\local\persistent\planning;
use mod_competvet\local\persistent\situation;
use mod_competvet\tests\test_data_definition;
use mod_competvet\utils;

/**
 * Orphan detection and fix tests for the plannings API.
 *
 * An orphaned student is a user who has data attached to a planning (observations,
 * certifications, cases or grades) but who is no longer a regular student of the
 * planning's group: either they left the group or they lost the student role.
 * A fix is proposed only when the issue can be fixed (the user left a group).
 *
 * @package     mod_competvet
 * @copyright   2026 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers      \mod_competvet\local\api\plannings
 * @covers      \mod_competvet\local\api\grading
 */
final class orphan_test extends advanced_testcase {
    use test_data_definition;

    /**
     * Set up the test fixture.
     *
     * @return void
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->prepare_scenario('set_1');
        $this->set_current_date();
        $this->setAdminUser();
    }

    /**
     * Detect an orphaned student (a record on a planning the student is not a group member of).
     *
     * @return void
     */
    public function test_detects_orphaned_student(): void {
        $planning = $this->get_sit1_planning(0); // Group of student1.
        $student2 = core_user::get_user_by_username('student2');

        // User student2 is not a member of this planning's group.
        $this->assertFalse(groups_is_member($planning->get('groupid'), $student2->id));
        $this->create_orphan_observation($planning, $student2->id, $student2->id);

        $orphans = plannings::get_orphaned_students_for_planning_id($planning->get('id'));
        $this->assertCount(1, $orphans);
        $this->assertArrayHasKey($student2->id, $orphans);
    }

    /**
     * Regression: orphans are still detected on a past-dated planning whose group still exists.
     *
     * @return void
     */
    public function test_detects_orphaned_student_on_past_planning(): void {
        global $USER;
        $planning = $this->get_sit1_planning(0); // Current-week planning (group of student1).
        $student2 = core_user::get_user_by_username('student2');
        $oneweek = 60 * 60 * 24 * 7;

        // Create a past-dated planning (the week before the current one) for the same live group.
        $pastplanning = new planning(0, (object) [
            'situationid' => $planning->get('situationid'),
            'groupid' => $planning->get('groupid'),
            'startdate' => $planning->get('startdate') - $oneweek,
            'enddate' => $planning->get('startdate') - 1,
            'session' => '2023',
        ]);
        $pastplanning->create();

        // The orphan student2 is not a member of the past planning's group.
        $this->assertFalse(groups_is_member($pastplanning->get('groupid'), $student2->id));
        $this->create_orphan_observation($pastplanning, $student2->id, $student2->id);

        // The orphan is still detected even though the planning is in the past.
        $orphans = plannings::get_orphaned_students_for_planning_id($pastplanning->get('id'));
        $this->assertCount(1, $orphans);
        $this->assertArrayHasKey($student2->id, $orphans);

        // It is also merged into the grading roster with the orphan flag and a fix proposal.
        $infos = grading::get_planning_infos_for_grading([$pastplanning->get('id')], $USER->id, true);
        $this->assertCount(1, $infos);
        $students = $infos[0]['stats']['students'];
        $this->assertCount(2, $students); // 1 member (student1) + 1 orphan (student2).
        $byid = [];
        foreach ($students as $groupmember) {
            $byid[$groupmember->id] = $groupmember;
        }
        $this->assertTrue($byid[$student2->id]->isorphan);
        $this->assertArrayHasKey('fixinfo', (array) $byid[$student2->id]);
    }

    /**
     * A student who is a group member is never reported as an orphan.
     *
     * @return void
     */
    public function test_no_false_positive_for_group_member(): void {
        $planning = $this->get_sit1_planning(0); // Group of student1.
        $student1 = core_user::get_user_by_username('student1');

        $this->assertTrue(groups_is_member($planning->get('groupid'), $student1->id));
        $this->create_orphan_observation($planning, $student1->id, $student1->id);

        $orphans = plannings::get_orphaned_students_for_planning_id($planning->get('id'));
        $this->assertSame([], $orphans);
    }

    /**
     * A student who keeps the group membership but lost the student role is detected as an orphan,
     * with no fix proposal (the issue cannot be fixed by moving or re-adding to a group).
     *
     * @return void
     */
    public function test_detects_orphaned_student_without_student_role(): void {
        global $USER;
        $planning = $this->get_sit1_planning(0); // Group of student1.
        $student1 = core_user::get_user_by_username('student1');
        $groupid = $planning->get('groupid');
        $group = groups_get_group($groupid);
        $contextid = competvet::get_from_situation_id($planning->get('situationid'))->get_context()->id;

        // The user student1 is a member of the planning's group. Remove only their student role assignment
        // (keeping the enrolment and the group membership) to simulate a role change.
        $this->assertTrue(groups_is_member($groupid, $student1->id));
        $coursecontext = \context_course::instance($group->courseid);
        foreach (utils::get_student_roles_id() as $studentroleid) {
            role_unassign_all([
                'userid' => $student1->id,
                'contextid' => $coursecontext->id,
                'roleid' => $studentroleid,
            ]);
        }
        // The group membership is intact, but the user is no longer a student.
        $this->assertTrue(groups_is_member($groupid, $student1->id));
        $this->assertFalse(utils::is_student($student1->id, $contextid));

        $this->create_orphan_observation($planning, $student1->id, $student1->id);

        // Detected as an orphan even though the group membership is intact.
        $orphans = plannings::get_orphaned_students_for_planning_id($planning->get('id'));
        $this->assertCount(1, $orphans);
        $this->assertArrayHasKey($student1->id, $orphans);

        // Not fixable: no fix proposal is returned.
        $this->assertSame([], plannings::find_orphan_fix($student1->id, $planning->get('id')));

        // Merged into the grading roster with the orphan flag but an empty (no) fix proposal.
        $infos = grading::get_planning_infos_for_grading([$planning->get('id')], $USER->id, true);
        $byid = [];
        foreach ($infos[0]['stats']['students'] as $groupmember) {
            $byid[$groupmember->id] = $groupmember;
        }
        $this->assertTrue($byid[$student1->id]->isorphan);
        $this->assertSame([], (array) $byid[$student1->id]->fixinfo);
    }

    /**
     * A planning without any CompetVet records has no orphans.
     *
     * @return void
     */
    public function test_no_records_no_orphans(): void {
        $planning = $this->get_sit1_planning(0);
        $this->assertSame([], plannings::get_orphaned_students_for_planning_id($planning->get('id')));
    }

    /**
     * Regression: the members-only student list is not polluted by orphan records.
     *
     * @return void
     */
    public function test_regression_students_members_only_with_orphan(): void {
        $planning = $this->get_sit1_planning(0); // Group of student1 only.
        $student1 = core_user::get_user_by_username('student1');
        $student2 = core_user::get_user_by_username('student2');
        $this->create_orphan_observation($planning, $student2->id, $student2->id);

        $students = plannings::get_students_for_planning_id($planning->get('id'));
        $this->assertCount(1, $students);
        $this->assertArrayHasKey($student1->id, $students);
        $this->assertArrayNotHasKey($student2->id, $students);
    }

    /**
     * The grading infos merge orphans into the roster with an isorphan flag and a fix proposal.
     *
     * @return void
     */
    public function test_grading_infos_include_orphan_with_fixinfo(): void {
        global $USER;
        $planning = $this->get_sit1_planning(0); // Group of student1.
        $student1 = core_user::get_user_by_username('student1');
        $student2 = core_user::get_user_by_username('student2');
        $this->create_orphan_observation($planning, $student2->id, $student2->id);

        $infos = grading::get_planning_infos_for_grading([$planning->get('id')], $USER->id, true);
        $this->assertCount(1, $infos);
        $students = $infos[0]['stats']['students'];
        $this->assertCount(2, $students);
        $this->assertSame(2, $infos[0]['stats']['nbstudents']);

        // Index the returned roster by user id.
        $byid = [];
        foreach ($students as $groupmember) {
            $byid[$groupmember->id] = $groupmember;
        }
        $this->assertArrayHasKey($student1->id, $byid);
        $this->assertArrayHasKey($student2->id, $byid);

        // The member is not flagged.
        $this->assertArrayNotHasKey('isorphan', (array) $byid[$student1->id]);
        $this->assertArrayNotHasKey('fixinfo', (array) $byid[$student1->id]);

        // The orphan is flagged and carries a fix proposal.
        $this->assertTrue($byid[$student2->id]->isorphan);
        $this->assertArrayHasKey('fixinfo', (array) $byid[$student2->id]);
        $this->assertSame('orphanfix:add', $byid[$student2->id]->fixinfo['action']);
    }

    /**
     * Regression: without orphans the grading infos return only group members, unflagged.
     *
     * @return void
     */
    public function test_grading_infos_regression_no_orphans(): void {
        global $USER;
        $planning = $this->get_sit1_planning(0); // Group of student1 only.
        $student1 = core_user::get_user_by_username('student1');

        $infos = grading::get_planning_infos_for_grading([$planning->get('id')], $USER->id);
        $this->assertCount(1, $infos);
        $students = $infos[0]['stats']['students'];
        $this->assertCount(1, $students);
        $this->assertSame((int) $student1->id, (int) $students[0]->id);
        $this->assertArrayNotHasKey('isorphan', (array) $students[0]);
        $this->assertArrayNotHasKey('fixinfo', (array) $students[0]);
    }

    /**
     * find_orphan_fix falls back to adding the user to the original planning group.
     *
     * @return void
     */
    public function test_find_orphan_fix_add(): void {
        $planning = $this->get_sit1_planning(0);
        $student2 = core_user::get_user_by_username('student2');
        $this->create_orphan_observation($planning, $student2->id, $student2->id);

        $fix = plannings::find_orphan_fix($student2->id, $planning->get('id'));
        $this->assertSame('orphanfix:add', $fix['action']);
        $this->assertSame((int) $student2->id, $fix['userid']);
        $this->assertSame($planning->get('groupid'), $fix['groupid']);
        $this->assertSame($planning->get('id'), $fix['planningid']);
        $this->assertSame($planning->get('id'), $fix['oldplanningid']);
        $groupname = groups_get_group_name($planning->get('groupid'));
        $this->assertSame(get_string('orphanfix:add', 'competvet', $groupname), $fix['fixstring']);
    }

    /**
     * find_orphan_fix prefers moving the user to a same-week planning of a group they belong to.
     *
     * @return void
     */
    public function test_find_orphan_fix_move(): void {
        $planning = $this->get_sit1_planning(0); // Group of student1.
        $otherplanning = $this->get_sit1_planning(1); // Group of student2.
        $group2 = $otherplanning->get('groupid');
        $student2 = core_user::get_user_by_username('student2');
        $this->assertTrue(groups_is_member($group2, $student2->id));

        $this->create_orphan_observation($planning, $student2->id, $student2->id);

        // Create a same-week planning in the same situation for the student's own group.
        $target = new planning(0, (object) [
            'situationid' => $planning->get('situationid'),
            'groupid' => $group2,
            'startdate' => $planning->get('startdate'),
            'enddate' => $planning->get('enddate'),
            'session' => '2023',
        ]);
        $target->create();

        $fix = plannings::find_orphan_fix($student2->id, $planning->get('id'));
        $this->assertSame('orphanfix:move', $fix['action']);
        $this->assertSame((int) $student2->id, $fix['userid']);
        $this->assertSame($group2, $fix['groupid']);
        $this->assertSame($target->get('id'), $fix['planningid']);
        $this->assertSame($planning->get('id'), $fix['oldplanningid']);
    }

    /**
     * fix_orphan_user (add) re-adds the user to the original planning group and resolves the orphan.
     *
     * @return void
     */
    public function test_fix_orphan_user_add(): void {
        $planning = $this->get_sit1_planning(0);
        $student2 = core_user::get_user_by_username('student2');
        $this->create_orphan_observation($planning, $student2->id, $student2->id);
        $groupname = groups_get_group_name($planning->get('groupid'));

        $result = plannings::fix_orphan_user(
            $student2->id,
            $planning->get('groupid'),
            $planning->get('id'),
            $planning->get('id'),
            'orphanfix:add'
        );

        $this->assertSame(get_string('orphanfixed:add', 'competvet', $groupname), $result);
        $this->assertTrue(groups_is_member($planning->get('groupid'), $student2->id));
        $this->assertSame([], plannings::get_orphaned_students_for_planning_id($planning->get('id')));
    }

    /**
     * fix_orphan_user (move) relocates the records and does not re-add the user to the old group.
     *
     * @return void
     */
    public function test_fix_orphan_user_move(): void {
        $planning = $this->get_sit1_planning(0); // Group of student1.
        $otherplanning = $this->get_sit1_planning(1); // Group of student2.
        $group2 = $otherplanning->get('groupid');
        $student2 = core_user::get_user_by_username('student2');
        $this->create_orphan_observation($planning, $student2->id, $student2->id);

        $target = new planning(0, (object) [
            'situationid' => $planning->get('situationid'),
            'groupid' => $group2,
            'startdate' => $planning->get('startdate'),
            'enddate' => $planning->get('enddate'),
            'session' => '2023',
        ]);
        $target->create();

        $groupname2 = groups_get_group_name($group2);
        $result = plannings::fix_orphan_user(
            $student2->id,
            $group2,
            $target->get('id'),
            $planning->get('id'),
            'orphanfix:move'
        );

        $this->assertSame(get_string('orphanfixed:move', 'competvet', $groupname2), $result);
        // The record moved from the source planning to the target planning.
        $this->assertSame(0, observation::count_records(['planningid' => $planning->get('id'), 'studentid' => $student2->id]));
        $this->assertSame(1, observation::count_records(['planningid' => $target->get('id'), 'studentid' => $student2->id]));
        // The user was NOT re-added to the original planning group.
        $this->assertFalse(groups_is_member($planning->get('groupid'), $student2->id));
        // The source planning no longer has the orphan.
        $this->assertSame([], plannings::get_orphaned_students_for_planning_id($planning->get('id')));
    }

    /**
     * fix_orphan_user (move) rejects a target planning that is not in the same week.
     *
     * @return void
     */
    public function test_fix_orphan_user_move_invalid_week(): void {
        $planning = $this->get_sit1_planning(0); // This week.
        $otherplanning = $this->get_sit1_planning(1); // Next week.
        $student2 = core_user::get_user_by_username('student2');
        $this->create_orphan_observation($planning, $student2->id, $student2->id);

        try {
            plannings::fix_orphan_user(
                $student2->id,
                $otherplanning->get('groupid'),
                $otherplanning->get('id'),
                $planning->get('id'),
                'orphanfix:move'
            );
            $this->fail('Expected a moodle_exception for an invalid move target.');
        } catch (\moodle_exception $e) {
            $this->assertSame('invaliddata', $e->errorcode);
        }
    }

    /**
     * fix_orphan_user rejects an unknown action.
     *
     * @return void
     */
    public function test_fix_orphan_user_invalid_action(): void {
        $planning = $this->get_sit1_planning(0);
        $student2 = core_user::get_user_by_username('student2');

        try {
            plannings::fix_orphan_user(
                $student2->id,
                $planning->get('groupid'),
                $planning->get('id'),
                $planning->get('id'),
                'orphanfix:unknown'
            );
            $this->fail('Expected a moodle_exception for an unknown action.');
        } catch (\moodle_exception $e) {
            $this->assertSame('invaliddata', $e->errorcode);
        }
    }

    /**
     * Get a SIT1 planning by its week offset (0 = base week, 1 = next week).
     *
     * @param int $weekoffset
     * @return planning
     */
    private function get_sit1_planning(int $weekoffset): planning {
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $oneweek = 60 * 60 * 24 * 7;
        $startdate = planning::round_start_date(self::get_start_date()->getTimestamp() + ($weekoffset * $oneweek));
        return planning::get_record([
            'situationid' => $situation->get('id'),
            'session' => '2023',
            'startdate' => $startdate,
        ]);
    }

    /**
     * Create an observation record directly on a planning, bypassing the write guard.
     *
     * @param planning $planning
     * @param int $studentid
     * @param int $observerid
     * @return void
     */
    private function create_orphan_observation(planning $planning, int $studentid, int $observerid): void {
        $observation = new observation(0);
        $observation->set('planningid', $planning->get('id'));
        $observation->set('studentid', $studentid);
        $observation->set('observerid', $observerid);
        $observation->set('status', observation::STATUS_NOTSTARTED);
        $observation->set('category', observation::CATEGORY_EVAL_OBSERVATION);
        $observation->create();
    }
}
