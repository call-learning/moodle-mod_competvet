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
namespace local\api;
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/mod/competvet/tests/test_data_definition.php');

use advanced_testcase;
use core_user;
use mod_competvet\local\api\observations;
use mod_competvet\local\api\plannings;
use mod_competvet\local\persistent\criterion;
use mod_competvet\local\persistent\observation;
use mod_competvet\local\persistent\observation_comment;
use mod_competvet\local\persistent\observation_criterion_comment;
use mod_competvet\local\persistent\observation_criterion_level;
use mod_competvet\local\persistent\situation;
use test_data_definition;

/**
 * Observations API test
 *
 * @package     mod_competvet
 * @copyright   2023 CALL Learning <contact@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observations_test extends advanced_testcase {
    use test_data_definition;

    public static function data_get_all_with_planning_for_user() {
        return [
            'student1' =>
                [
                    'category' => observation::CATEGORY_EVAL_AUTOEVAL,
                    'student' => 'student1',
                    'observer' => 'observer1',
                    'context' => 'A context',
                    'comments' => [
                        ['type' => observation_comment::OBSERVATION_COMMENT, 'comment' => 'A comment'],
                        ['type' => observation_comment::OBSERVATION_PRIVATE_COMMENT, 'comment' => 'Another comment'],
                    ],
                    'criteria' => [
                        ['id' => 'Q001', 'level' => 1],
                        ['id' => 'Q002', 'comment' => 'Comment 1'],
                        ['id' => 'Q003', 'comment' => 'Comment 2'],
                    ],
                ],
        ];
    }

    /**
     * Setup the test
     *
     * @return void
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser(); // Needed for report builder to work.
        $this->prepare_scenario('set_2');
    }

    /**
     * Get all with planning for user
     *
     * @param string $username
     * @param array $expected
     * @return void
     * @covers       \mod_competvet\local\api\observations::create_observation
     * @dataProvider data_get_all_with_planning_for_user
     */
    public function test_create_observation(
        int $category,
        string $student,
        string $observer,
        string $context,
        array $comments,
        array $criteria
    ) {
        $student = core_user::get_user_by_username($student);
        $observer = core_user::get_user_by_username($observer);
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $plannings = plannings::get_plannings_for_situation_id($situation->get('id'), $student->id);
        $planning = array_shift($plannings);
        // Parse data so to change criteria shortname into criteriaid.
        $criteria = array_map(function ($value) {
            $value['id'] = criterion::get_record(['idnumber' => $value['id']])->get('id');
            return $value;
        }, $criteria);

        $observationid =
            observations::create_observation(
                $category,
                $planning['id'],
                $student->id,
                $observer->id,
                $context,
                $comments,
                $criteria
            );
        $observation = observation::get_record(['id' => $observationid]);
        $this->assertEquals($planning['id'], $observation->get('planningid'));
        $this->assertEquals($student->id, $observation->get('studentid'));
        $this->assertEquals($observer->id, $observation->get('observerid'));
        $this->assertEquals($category, $observation->get('category'));
        $this->assertEquals(3, observation_comment::count_records(['observationid' => $observationid]));
        $this->assertEquals(1, observation_comment::count_records(['observationid' => $observationid,
            'type' => observation_comment::OBSERVATION_CONTEXT, ]));
        $this->assertEquals(1, observation_comment::count_records(['observationid' => $observationid,
            'type' => observation_comment::OBSERVATION_PRIVATE_COMMENT, ]));
        $this->assertEquals(7, observation_criterion_level::count_records(['observationid' => $observationid]));
        $this->assertEquals(33, observation_criterion_comment::count_records(['observationid' => $observationid]));
        foreach (array_filter($criteria, fn($crit) => isset($crit['level'])) as $critelevel) {
            $this->assertEquals(
                $critelevel['level'],
                observation_criterion_level::get_record(
                    [
                        'observationid' => $observationid,
                        'criterionid' => $critelevel['id'],
                    ]
                )->get('level')
            );
        }
    }

    /**
     * Get all with planning for user
     *
     * @param string $username
     * @param array $expected
     * @return void
     * @covers       \mod_competvet\local\api\observations::create_observation
     * @dataProvider data_get_all_with_planning_for_user
     */
    public function test_edit_observation(
        int $category,
        string $student,
        string $observer,
        string $context,
        array $comments,
        array $criteria
    ) {
        $student = core_user::get_user_by_username($student);
        $observer = core_user::get_user_by_username($observer);
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $plannings = plannings::get_plannings_for_situation_id($situation->get('id'), $student->id);
        $planning = array_shift($plannings);
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_competvet');
        $newobs = $generator->create_observation_with_comment([
            'planningid' => $planning['id'],
            'studentid' => $student->id,
            'observerid' => $observer->id,
            'category' => $category,
            'context' => $context,
            'comments' => $comments,
            'criteria' => $criteria,

        ]);
        observations::edit_observation(
            $newobs->id,
            'A new context',
        );
        $this->assertEquals('A new context', observation_comment::get_record([
            'observationid' => $newobs->id,
            'type' => observation_comment::OBSERVATION_CONTEXT,
        ])->get('comment'));

        observations::edit_observation(
            $newobs->id,
            null,
            [
                ['type' => observation_comment::OBSERVATION_COMMENT, 'comment' => 'A new comment'],
            ]
        );
        $this->assertEquals('A new comment', observation_comment::get_record([
            'observationid' => $newobs->id,
            'type' => observation_comment::OBSERVATION_COMMENT,
        ])->get('comment'));
    }
}
