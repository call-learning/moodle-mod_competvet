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
use mod_competvet\local\persistent\criterion;
use mod_competvet\local\persistent\observation;
use mod_competvet\local\persistent\observation_criterion_level;
use mod_competvet\local\persistent\planning;
use mod_competvet\local\persistent\situation;
use mod_competvet\tests\test_data_definition;

/**
 * Progression API test
 *
 * @package     mod_competvet
 * @copyright   2024 CALL Learning <contact@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\mod_competvet\local\api\progression::class)]
final class progression_test extends advanced_testcase {
    use test_data_definition;

    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->prepare_scenario('set_3');
        $this->set_current_date();
    }

    public function test_no_observations_returns_not_evaluated(): void {
        $student = core_user::get_user_by_username('student1');
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $plannings = plannings::get_plannings_for_situation_id($situation->get('id'), $student->id);
        $planning = array_shift($plannings);

        $newplanning = new planning(0);
        $newplanning->set('situationid', $situation->get('id'));
        $newplanning->set('groupid', $planning->get('groupid'));
        $newplanning->set('startdate', $planning->get('startdate') + 86400);
        $newplanning->set('enddate', $planning->get('enddate') + 86400);
        $newplanning->set('session', 'no-obs-test');
        $newplanning->create();

        $progression = progression::get_student_progression($newplanning->get('id'), $student->id);

        $this->assertNotEmpty($progression);
        foreach ($progression as $criterionid => $criterionprogression) {
            $this->assertEquals(progression::STATE_NOT_EVALUATED, $criterionprogression->state);
            $this->assertNull($criterionprogression->bestlevel);
            $this->assertEquals(0, $criterionprogression->totalobservations);
        }
    }

    public function test_single_observation_acquired(): void {
        $student = core_user::get_user_by_username('student1');
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $plannings = plannings::get_plannings_for_situation_id($situation->get('id'), $student->id);
        $planning = array_shift($plannings);

        $progression = progression::get_student_progression($planning->get('id'), $student->id);

        $this->assertArrayHasKey('Q001', $progression);
        $q001 = $progression['Q001'];
        $this->assertEquals(progression::STATE_ACQUIRED, $q001->state);
        $this->assertEquals(5, $q001->bestlevel);
        $this->assertEquals(1, $q001->totalobservations);
    }

    public function test_single_observation_not_acquired(): void {
        $student = core_user::get_user_by_username('student1');
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $plannings = plannings::get_plannings_for_situation_id($situation->get('id'), $student->id);
        $planning = $plannings[1] ?? null;
        if ($planning === null) {
            $this->markTestSkipped('Second planning not found in set_3');
        }

        $observer = core_user::get_user_by_username('observer1');
        $criterionid = criterion::get_record(['idnumber' => 'Q001'])->get('id');

        observations::create_observation(
            observation::CATEGORY_EVAL_OBSERVATION,
            $planning->get('id'),
            $student->id,
            $observer->id,
            'Low score observation',
            [],
            [['id' => $criterionid, 'level' => 1]]
        );

        $progression = progression::get_student_progression($planning->get('id'), $student->id);

        $this->assertArrayHasKey('Q001', $progression);
        $q001 = $progression['Q001'];
        $this->assertEquals(progression::STATE_EVALUATED_NOT_ACQUIRED, $q001->state);
        $this->assertEquals(1, $q001->bestlevel);
    }

    public function test_repeated_observations_keep_best_level(): void {
        $student = core_user::get_user_by_username('student1');
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $plannings = plannings::get_plannings_for_situation_id($situation->get('id'), $student->id);
        $planning = array_shift($plannings);

        $observer = core_user::get_user_by_username('observer1');
        $criterionid = criterion::get_record(['idnumber' => 'Q001'])->get('id');

        observations::create_observation(
            observation::CATEGORY_EVAL_OBSERVATION,
            $planning->get('id'),
            $student->id,
            $observer->id,
            'Second observation with lower score',
            [],
            [['id' => $criterionid, 'level' => 2]]
        );

        $progression = progression::get_student_progression($planning->get('id'), $student->id);

        $q001 = $progression['Q001'];
        $this->assertEquals(progression::STATE_ACQUIRED, $q001->state);
        $this->assertEquals(5, $q001->bestlevel);
        $this->assertEquals(2, $q001->totalobservations);
    }

    public function test_sub_criteria_excluded(): void {
        $student = core_user::get_user_by_username('student1');
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $plannings = plannings::get_plannings_for_situation_id($situation->get('id'), $student->id);
        $planning = array_shift($plannings);

        $progression = progression::get_student_progression($planning->get('id'), $student->id);

        foreach ($progression as $criterionid => $criterionprogression) {
            $criterion = criterion::get_record(['id' => $criterionid]);
            $this->assertEquals(0, $criterion->get('parentid'),
                "Sub-criterion {$criterionprogression->criterionlabel} should be excluded");
        }
    }
