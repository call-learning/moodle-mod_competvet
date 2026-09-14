<?php
// This file is part of Moodle - https://moodle.org/
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

namespace mod_competvet;

use advanced_testcase;
use context_course;
use core_user;
use mod_competvet\local\persistent\situation;
use mod_competvet\tests\test_data_definition;

/**
 * CompetVet visibility test
 *
 * @package     mod_competvet
 * @copyright   2023 CALL Learning <contact@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\mod_competvet\competvet::class)]
final class competvet_test extends advanced_testcase {
    use test_data_definition;

    /**
     * Setup the test
     *
     * @return void
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->prepare_scenario('set_2');
        $this->set_current_date();
    }

    /**
     * The strict view access must deny a hidden activity, even for users holding
     * moodle/course:viewhiddenactivities and for siteadmins, while the capability-based
     * access still allows them.
     *
     * @return void
     */
    public function test_has_strict_view_access(): void {
        global $DB;
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $competvet = competvet::get_from_situation_id($situation->get('id'));
        $teacher = core_user::get_user_by_username('teacher1');
        $admin = core_user::get_user_by_username('admin');

        // Grant the capability to view hidden activities to the teacher (via the teacher role).
        $context = context_course::instance($competvet->get_course_module()->course);
        $teacherrole = $DB->get_record('role', ['shortname' => 'teacher'], '*', MUST_EXIST);
        assign_capability('moodle/course:viewhiddenactivities', CAP_ALLOW, $teacherrole->id, $context->id);
        accesslib_clear_all_caches_for_unit_testing();

        // While the activity is visible, both access checks pass.
        $this->assertTrue($competvet->has_view_access($teacher->id));
        $this->assertTrue($competvet->has_strict_view_access($teacher->id));

        // Hide the activity.
        set_coursemodule_visible($competvet->get_course_module_id(), 0);

        // The capability-based check still passes (the teacher can view hidden activities).
        $this->assertTrue($competvet->has_view_access($teacher->id));
        // But the strict check now fails.
        $this->assertFalse($competvet->has_strict_view_access($teacher->id));

        // A siteadmin still passes the capability-based check, but not the strict one.
        $this->assertTrue($competvet->has_view_access($admin->id));
        $this->assertFalse($competvet->has_strict_view_access($admin->id));
    }
}
