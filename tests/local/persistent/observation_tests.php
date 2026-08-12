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

namespace mod_competvet\local\persistent;
use advanced_testcase;
use core_user;
use mod_competvet\tests\test_data_definition;

/**
 * Situations API persistent test
 *
 * @package     mod_competvet
 * @copyright   2023 CALL Learning <contact@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class observation_tests extends advanced_testcase {
    use test_data_definition;

    /**
     * Setup the test
     *
     * @return void
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->prepare_scenario('set_3');
        $this->set_current_date();
    }

    /**
     * Test get_all_with_planning_for_user
     *
     */
    public function test_observation_comment() {
        $user = core_user::get_user_by_username('student1');
        $situation1 = situation::get_record(['shortname' => 'SIT1']);
        $plannings = array_values(planning::get_records(['situationid' => $situation1->get('id')]));
        $planning = $plannings[0];

        $observations = array_values(observation::get_records(['planningid' => $planning->get('id')]));
        $this->assertCount(2, $observations);
        $observation = $observations[0];
        $comments = $observation->get_comments();
        $this->assertcount(3, $comments);
        $commenttypes = array_map(fn($comment) => $comment->get('type'), $comments);
        sort($commenttypes);
        $this->assertEquals([1, 2, 13], $commenttypes);
        $criteriacomments = $observation->get_criteria_comments();
        $this->assertCount(2, $criteriacomments);
        $this->assertEquals(
            ['Comment autoeval 1', 'Comment autoeval 2'],
            array_map(fn($comment) => $comment->get('comment'), $criteriacomments)
        );
    }

    /**
     * Empty grades include both null and the no-grade sentinel.
     *
     * @return void
     */
    public function test_empty_grade_rule(): void {
        $this->assertTrue(observation_criterion_level::is_an_empty_level(null));
        $this->assertTrue(observation_criterion_level::is_an_empty_level(
            observation_criterion_level::NO_GRADE_LEVEL
        ));
        $this->assertFalse(observation_criterion_level::is_an_empty_level(0));
        $this->assertFalse(observation::has_usable_grade([null, observation_criterion_level::NO_GRADE_LEVEL]));
        $this->assertTrue(observation::has_usable_grade([observation_criterion_level::NO_GRADE_LEVEL, 0]));
    }
}
