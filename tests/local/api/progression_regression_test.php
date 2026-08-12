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

use core_user;
use mod_competvet\local\persistent\criterion;
use mod_competvet\local\persistent\observation;
use mod_competvet\local\persistent\observation_criterion_level;
use mod_competvet\local\persistent\planning;
use mod_competvet\local\persistent\situation;
use mod_competvet\tests\test_data_definition;

/**
 * Progression API regression tests.
 *
 * @package     mod_competvet
 * @copyright   2024 CALL Learning <contact@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\mod_competvet\local\api\progression::class)]
final class progression_regression_test extends \advanced_testcase {
    use test_data_definition;

    protected ?situation $situation = null;
    protected ?planning $planning = null;
    protected int $studentid = 0;
    protected int $observerid = 0;

    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->prepare_scenario('set_3');
        $this->set_current_date();

        $this->situation = situation::get_record(['shortname' => 'SIT1']);
        $plannings = plannings::get_plannings_for_situation_id($this->situation->get('id'), 1);
        $this->planning = array_shift($plannings);

        $this->studentid = core_user::get_user_by_username('student1')->id;
        $this->observerid = core_user::get_user_by_username('observer1')->id;
    }

    public function test_aggregation_rules_for_all_states(): void {
        $progression = progression::get_student_progression($this->planning->get('id'), $this->studentid);

        foreach ($progression as $criterionid => $criterionprogression) {
            $this->assertContains(
                $criterionprogression->state,
                [progression::STATE_NOT_EVALUATED, progression::STATE_EVALUATED_NOT_ACQUIRED, progression::STATE_ACQUIRED]
            );
        }

        $summary = progression::get_progression_summary($this->planning->get('id'), $this->studentid);
        $countacquired = 0;
        $countnotacquired = 0;
        $countnotevaluated = 0;

        foreach ($progression as $criterion) {
            switch ($criterion->state) {
                case progression::STATE_ACQUIRED: $countacquired++; break;
                case progression::STATE_EVALUATED_NOT_ACQUIRED: $countnotacquired++; break;
                case progression::STATE_NOT_EVALUATED: $countnotevaluated++; break;
            }
        }

        $this->assertEquals($summary->acquired, $countacquired);
        $this->assertEquals($summary->evaluated_not_acquired, $countnotacquired);
        $this->assertEquals($summary->not_evaluated, $countnotevaluated);
        $this->assertEquals($summary->total, $summary->acquired + $summary->evaluated_not_acquired + $summary->not_evaluated);
    }

    public function test_below_threshold_state_is_evaluated_not_acquired(): void {
        $criterionid = criterion::get_record(['idnumber' => 'Q001'])->get('id');

        observations::create_observation(
            observation::CATEGORY_EVAL_OBSERVATION, $this->planning->get('id'),
            $this->studentid, $this->observerid, 'Low score', [],
            [['id' => $criterionid, 'level' => 1]]
        );

        $progression = progression::get_student_progression($this->planning->get('id'), $this->studentid);
        $this->assertEquals(progression::STATE_EVALUATED_NOT_ACQUIRED, $progression['Q001']->state);
        $this->assertEquals(1, $progression['Q001']->bestlevel);
    }

    public function test_at_or_above_threshold_state_is_acquired(): void {
        $criterionid = criterion::get_record(['idnumber' => 'Q001'])->get('id');

        observations::create_observation(
            observation::CATEGORY_EVAL_OBSERVATION, $this->planning->get('id'),
            $this->studentid, $this->observerid, 'High score', [],
            [['id' => $criterionid, 'level' => progression::MAX_USABLE_LEVEL]]
        );

        $progression = progression::get_student_progression($this->planning->get('id'), $this->studentid);
        $this->assertEquals(progression::STATE_ACQUIRED, $progression['Q001']->state);
        $this->assertEquals(progression::MAX_USABLE_LEVEL, $progression['Q001']->bestlevel);
    }

    public function test_repeated_evaluations_keep_best_level(): void {
        $criterionid = criterion::get_record(['idnumber' => 'Q001'])->get('id');

        observations::create_observation(
            observation::CATEGORY_EVAL_OBSERVATION, $this->planning->get('id'),
            $this->studentid, $this->observerid, 'Obs1', [],
            [['id' => $criterionid, 'level' => 3]]
        );
        observations::create_observation(
            observation::CATEGORY_EVAL_OBSERVATION, $this->planning->get('id'),
            $this->studentid, $this->observerid, 'Obs2', [],
            [['id' => $criterionid, 'level' => 5]]
        );
        observations::create_observation(
            observation::CATEGORY_EVAL_OBSERVATION, $this->planning->get('id'),
            $this->studentid, $this->observerid, 'Obs3', [],
            [['id' => $criterionid, 'level' => 2]]
        );

        $progression = progression::get_student_progression($this->planning->get('id'), $this->studentid);
        $this->assertEquals(progression::STATE_ACQUIRED, $progression['Q001']->state);
        $this->assertEquals(5, $progression['Q001']->bestlevel);
        $this->assertEquals(3, $progression['Q001']->totalobservations);
    }

    public function test_historical_evaluations_contribute_to_progression(): void {
        $criterionid = criterion::get_record(['idnumber' => 'Q001'])->get('id');

        $observation = new \mod_competvet\local\persistent\observation(0);
        $observation->set('category', observation::CATEGORY_EVAL_OBSERVATION);
        $observation->set('planningid', $this->planning->get('id'));
        $observation->set('studentid', $this->studentid);
        $observation->set('observerid', $this->observerid);
        $observation->set('status', observation::STATUS_COMPLETED);
        $observation->set('timemodified', strtotime('-2 years'));
        $observation->create();

        $level = new observation_criterion_level(0);
        $level->set('observationid', $observation->get('id'));
        $level->set('criterionid', $criterionid);
        $level->set('level', 5);
        $level->create();

        $progression = progression::get_student_progression($this->planning->get('id'), $this->studentid);
        $this->assertEquals(progression::STATE_ACQUIRED, $progression['Q001']->state);
        $this->assertEquals(5, $progression['Q001']->bestlevel);
        $this->assertEquals(1, $progression['Q001']->totalobservations);
    }

    public function test_legacy_data_without_modern_metadata_still_shows(): void {
        $criterionid = criterion::get_record(['idnumber' => 'Q001'])->get('id');

        $observation = new \mod_competvet\local\persistent\observation(0);
        $observation->set('category', observation::CATEGORY_EVAL_OBSERVATION);
        $observation->set('planningid', $this->planning->get('id'));
        $observation->set('studentid', $this->studentid);
        $observation->set('observerid', $this->observerid);
        $observation->set('status', observation::STATUS_COMPLETED);
        $observation->create();

        $level = new observation_criterion_level(0);
        $level->set('observationid', $observation->get('id'));
        $level->set('criterionid', $criterionid);
        $level->set('level', 4);
        $level->create();

        $progression = progression::get_student_progression($this->planning->get('id'), $this->studentid);
        $this->assertArrayHasKey('Q001', $progression);
        $this->assertEquals(progression::STATE_EVALUATED_NOT_ACQUIRED, $progression['Q001']->state);
    }

    public function test_batch_progression_returns_data_for_multiple_students(): void {
        $student2id = core_user::get_user_by_username('student2')->id;
        $batch = progression::get_batch_progression($this->planning->get('id'), [$this->studentid, $student2id]);

        $this->assertArrayHasKey($this->studentid, $batch);
        $this->assertArrayHasKey($student2id, $batch);
        foreach ([$this->studentid, $student2id] as $sid) {
            $this->assertArrayHasKey('progression', $batch[$sid]);
            $this->assertArrayHasKey('summary', $batch[$sid]);
            $this->assertEquals($sid, $batch[$sid]['studentid']);
        }
    }

    public function test_state_labels_and_css_classes_consistency(): void {
        foreach ([progression::STATE_NOT_EVALUATED, progression::STATE_EVALUATED_NOT_ACQUIRED, progression::STATE_ACQUIRED] as $state) {
            $this->assertNotEmpty(progression::get_state_label($state));
            $this->assertNotEmpty(progression::get_state_css_class($state));
            $this->assertNotEmpty(progression::get_state_icon($state));
        }
    }

    public function test_custom_threshold_affects_acquisition_state(): void {
        $criterionid = criterion::get_record(['idnumber' => 'Q001'])->get('id');

        observations::create_observation(
            observation::CATEGORY_EVAL_OBSERVATION, $this->planning->get('id'),
            $this->studentid, $this->observerid, 'Medium', [],
            [['id' => $criterionid, 'level' => 3]]
        );

        $p1 = progression::get_student_progression($this->planning->get('id'), $this->studentid);
        $this->assertEquals(progression::STATE_EVALUATED_NOT_ACQUIRED, $p1['Q001']->state);

        $p2 = progression::get_student_progression($this->planning->get('id'), $this->studentid, 50);
        $this->assertEquals(progression::STATE_ACQUIRED, $p2['Q001']->state);
    }

    public function test_report_output_identifies_missing_competencies(): void {
        $newplanning = new planning(0);
        $newplanning->set('situationid', $this->situation->get('id'));
        $newplanning->set('groupid', $this->planning->get('groupid'));
        $newplanning->set('startdate', $this->planning->get('startdate') + 86400);
        $newplanning->set('enddate', $this->planning->get('enddate') + 86400);
        $newplanning->set('session', 'report-test');
        $newplanning->create();

        $summary = progression::get_progression_summary($newplanning->get('id'), $this->studentid);
        $this->assertEquals($summary->not_evaluated, $summary->total);
        $this->assertEquals(0, $summary->acquired);
        $this->assertEquals(0, $summary->evaluated_not_acquired);
    }

    public function test_summary_counts_are_accurate_when_all_evaluated(): void {
        $criteria = $this->situation->get_eval_criteria();
        $criterionids = [];
        foreach ($criteria as $crit) {
            if ($crit->get('parentid') == 0) {
                $criterionids[] = $crit->get('id');
            }
        }

        foreach ($criterionids as $criterionid) {
            observations::create_observation(
                observation::CATEGORY_EVAL_OBSERVATION, $this->planning->get('id'),
                $this->studentid, $this->observerid, 'Eval', [],
                [['id' => $criterionid, 'level' => 5]]
            );
        }

        $summary = progression::get_progression_summary($this->planning->get('id'), $this->studentid);
        $this->assertEquals($summary->acquired, $summary->total);
        $this->assertEquals(0, $summary->not_evaluated);
        $this->assertEquals(0, $summary->evaluated_not_acquired);
    }

    public function test_sub_criteria_excluded_from_progression(): void {
        $progression = progression::get_student_progression($this->planning->get('id'), $this->studentid);
        foreach ($progression as $criterionid => $criterionprogression) {
            $criterion = criterion::get_record(['id' => $criterionid]);
            $this->assertEquals(0, $criterion->get('parentid'));
        }
    }
}
