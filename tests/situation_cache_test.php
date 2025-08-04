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
// phpcs:ignoreFile

namespace mod_competvet;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/mod/competvet/tests/test_data_definition.php');
require_once($CFG->dirroot . '/mod/competvet/lib.php');

use advanced_testcase;
use core_user;
use mod_competvet\local\persistent\situation;
use test_data_definition;

/**
 * Situation cache tests
 *
 * @package     mod_competvet
 * @copyright   2023 CALL Learning <contact@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class situation_cache_tests extends advanced_testcase {
    use test_data_definition;

    /**
     * Set up the test
     *
     * @return void
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Test create situation cache
     *
     * @return void
     */
    public function test_create_situation_cache() {
        global $DB;
        $this->prepare_scenario('set_2');
        $student1 = core_user::get_user_by_username('student1');
        $this->setUser($student1);
        $situations = situation::get_all_situations_id_for($student1->id);
        $this->assertCount(1, $situations);
        $this->setAdminUser();
        $course = $DB->get_record('course', ['shortname' => 'course 1']);
        $this->getDataGenerator()->create_module('competvet', ['course' => $course->id]);
        $this->setUser($student1);
        $situations = situation::get_all_situations_id_for($student1->id);
        $this->assertCount(2, $situations);
    }

    /**
     * Test delete situation cache
     *
     * @covers \mod_competvet\local\persistent\situation::get_all_situations_id_for
     * @return void
     */
    public function test_delete_situation_cache() {
        global $DB;
        $this->prepare_scenario('set_2');
        $student1 = core_user::get_user_by_username('student1');
        $course = $DB->get_record('course', ['shortname' => 'course 1']);
        $situation = $this->getDataGenerator()->create_module('competvet', ['course' => $course->id]);
        $this->setUser($student1);
        $situations = situation::get_all_situations_id_for($student1->id);
        $this->assertCount(2, $situations);
        $modinfo = get_fast_modinfo($course);
        [$course, $cm] = get_course_and_cm_from_instance($situation->id, 'competvet');
        course_delete_module($cm->id);
        $this->run_all_adhoc_tasks();
        $situations = situation::get_all_situations_id_for($student1->id);
        $this->assertCount(1, $situations);
    }

    /**
     * Test enrol student
     *
     * @covers \mod_competvet\local\persistent\situation::get_all_situations_id_for
     * @return void
     */
    public function test_enrol_student() {
        global $DB;
        $this->prepare_scenario('set_2');
        $course = $DB->get_record('course', ['shortname' => 'course 1']);
        $studentx = $this->getDataGenerator()->create_and_enrol($course);
        $situations = situation::get_all_situations_id_for($studentx->id);
        $this->assertCount(1, $situations);
    }

    /**
     * Test enrol student
     *
     * @covers \mod_competvet\local\persistent\situation::get_all_situations_id_for
     * @return void
     */
    public function test_unenrol_student() {
        global $DB;
        $this->prepare_scenario('set_2');
        $course = $DB->get_record('course', ['shortname' => 'course 1']);
        $student1 = core_user::get_user_by_username('student1');
        $situations = situation::get_all_situations_id_for($student1->id);
        $this->assertCount(1, $situations);
        $plugin = enrol_get_plugin('manual');
        $manualenrol = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'manual'], '*', MUST_EXIST);
        $plugin->unenrol_user($manualenrol, $student1->id);
        $situations = situation::get_all_situations_id_for($student1->id);
        $this->assertCount(0, $situations);
    }
}
