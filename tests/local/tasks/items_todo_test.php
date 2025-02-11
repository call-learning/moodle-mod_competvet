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

namespace local\tasks;
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/mod/competvet/tests/test_data_definition.php');

use advanced_testcase;
use DateTime;
use mod_competvet\competvet;
use mod_competvet\local\api\plannings;
use mod_competvet\local\persistent\planning;
use mod_competvet\local\persistent\situation;
use mod_competvet\task\end_of_planning;
use mod_competvet\task\items_todo;
use test_data_definition;

/**
 * Item Todo
 *
 * @package     mod_competvet
 * @copyright   2023 CALL Learning <contact@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class items_todo_test extends advanced_testcase {
    use test_data_definition;

    /**
     * Setup the test
     *
     * @return void
     */
    public static function items_todo_provider(): array {
        return [
            'planning ending tomorrow' => [
                'startdate' => new DateTime('-7 days'),
                'expectedsubjects' => [
                    ['[CompetVet] You have pending actions in your CompetVet task list SIT1', 'observer1@example.com'],
                    ['[CompetVet] You have pending actions in your CompetVet task list SIT1', 'observer2@example.com'],
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
     * @covers       \mod_competvet\task\items_todo::execute
     * @dataProvider items_todo_provider
     */
    public function test_item_todo(DateTime $startdate, array $expectedemails): void {
        $this->resetAfterTest();
        set_config('immediate_email', 1, 'mod_competvet');
        $this->setAdminUser(); // Needed for report builder to work.
        $this->prepare_data($startdate->getTimestamp());
        $emailsink = $this->redirectEmails();
        $endofplanningtasks = new items_todo();
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
