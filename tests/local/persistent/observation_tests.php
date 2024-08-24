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
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/mod/competvet/tests/test_data_definition.php');

use advanced_testcase;
use core_user;
use test_data_definition;

/**
 * Situations API persistent test
 *
 * @package     mod_competvet
 * @copyright   2023 CALL Learning <contact@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observation_tests extends advanced_testcase {
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
    }

    public function test_observation_comment() {
        $user = core_user::get_user_by_username('student1');
        $situation1 = situation::get_record(['shortname' => 'SIT1']);
        $planning = planning::get_records(['situationid' => $situation1->get('id')]);
        $planning = $planning[0];

        $observations = observation::get_records(['planningid' => $planning->get('id')]);
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
            ['Comment autoeval1', 'Comment autoeval2'],
            array_map(fn($comment) => $comment->get('comment'), $criteriacomments)
        );
    }
}
