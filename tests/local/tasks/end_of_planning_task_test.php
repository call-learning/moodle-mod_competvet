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

namespace mod_competvet\local\tasks;
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/mod/competvet/tests/test_data_definition.php');

use advanced_testcase;
use core\cron;
use DateTime;
use mod_competvet\competvet;
use mod_competvet\local\api\grades;
use mod_competvet\local\api\plannings;
use mod_competvet\local\persistent\planning;
use mod_competvet\local\persistent\situation;
use mod_competvet\task\end_of_planning;
use test_data_definition;

/**
 * End of planning task
 *
 * @package     mod_competvet
 * @copyright   2023 CALL Learning <contact@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class end_of_planning_task_test extends advanced_testcase {
    use test_data_definition;

    /**
     * Setup the test
     *
     * @return void
     */
    public static function end_of_planning_data_provider(): array {
        return [
            'planning ending yesterday' => [
                'startdate' => new DateTime('-8 days'),
                'expectedsubjects' => [
                    // Note here that no email is sent to teachers or editing teachers.
                    ['[CompetVet] You have students to grade in the rotation SIT1', 'evaluator1@example.com'],
                    ['[CompetVet] You have students to grade in the rotation SIT1', 'evaluator2@example.com'],
                ],
            ],
            'planning ending last week' => [
                'startdate' => new DateTime('-12 days'), // We should see both situations.
                'expectedsubjects' => [
                    ['[CompetVet] You have students to grade in the rotation SIT1', 'evaluator1@example.com'],
                    ['[CompetVet] You have students to grade in the rotation SIT2', 'evaluator1@example.com'],
                    ['[CompetVet] You have students to grade in the rotation SIT1', 'evaluator2@example.com'],
                    ['[CompetVet] You have students to grade in the rotation SIT2', 'evaluator2@example.com'],
                ],
            ],
        ];
    }

    /**
     * Test that the end of planning tasks sends an email when there are students to grade.
     *
     * @param DateTime $startdate
     * @param array $expectedemails
     * @return void
     * @covers       \mod_competvet\task\end_of_planning::execute
     * @dataProvider end_of_planning_data_provider
     */
    public function test_end_of_planning_without_grade(DateTime $startdate, array $expectedemails): void {
        $this->resetAfterTest();
        set_config('immediate_email', 1, 'mod_competvet');
        $this->setAdminUser(); // Needed for report builder to work.
        $this->prepare_data($startdate->getTimestamp());
        $emailsink = $this->redirectEmails();
        $endofplanningtasks = new end_of_planning();
        $endofplanningtasks->execute();
        $emails = $emailsink->get_messages();
        $this->assertCount(count($expectedemails), $emails);
        usort($emails, function($a, $b) {
            return $a->to === $b->to ? ($a->subject <=> $b->subject) : ($a->to < $b->to ? -1 : 1);
        });
        foreach ($emails as $index => $email) {
                $this->assertEquals($expectedemails[$index], [$email->subject, $email->to]);
        }
    }

    /**
     * Test that the end of planning tasks sends an email when there are students to grade.
     *
     * @param DateTime $startdate
     * @return void
     * @covers       \mod_competvet\task\end_of_planning::execute
     * @dataProvider end_of_planning_data_provider
     */
    public function test_end_of_planning_with_grade(DateTime $startdate): void {
        $this->resetAfterTest();
        set_config('immediate_email', 1, 'mod_competvet');
        $this->setAdminUser(); // Needed for report builder to work.
        $this->prepare_data($startdate->getTimestamp());
        // Set grades.
        $situations = situation::get_records();
        // Make sure all situation have been graded.
        foreach ($situations as $situation) {
            $competvet = competvet::get_from_situation($situation);
            $plannings = planning::get_records(['situationid' => $situation->get('id')]);
            foreach ($plannings as $planning) {
                $students = plannings::get_students_for_planning_id($planning->get('id'));
                foreach ($students as $student) {
                    $item = \grade_item::fetch([
                        'itemtype' => 'mod',
                        'itemmodule' => 'competvet',
                        'iteminstance' => $competvet->get_instance_id(),
                        'courseid' => $competvet->get_course_id(),
                    ]);
                    $item->update_final_grade($student->id, 10, null, "", FORMAT_HTML);
                }
            }
        }
        $emailsink = $this->redirectEmails();
        $endofplanningtasks = new end_of_planning();
        $endofplanningtasks->execute();
        $emails = $emailsink->get_messages();
        $this->assertCount(0, $emails);
    }

    /**
     * Prepare data
     *
     * @return void
     */
    private function prepare_data(int $timestart) {
        $generator = $this->getDataGenerator();
        $competvetgenerator = $generator->get_plugin_generator('mod_competvet');
        $this->generates_definition(
            $this->{'get_data_definition_set_5'}($timestart),
            $generator,
            $competvetgenerator
        );
    }
}
