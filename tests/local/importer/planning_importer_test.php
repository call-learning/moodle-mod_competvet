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

namespace mod_competvet\local\importer;
use advanced_testcase;
use mod_competvet\competvet;
use mod_competvet\local\persistent\planning;
use mod_competvet\local\persistent\planning_pause;
use mod_competvet\local\persistent\situation;
use mod_competvet\tests\test_data_definition;

/**
 * Planning Importer Grid Test
 *
 * @package     mod_competvet
 * @copyright   2025 CALL Learning <contact@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\mod_competvet\local\importer\planning_importer::class)]
final class planning_importer_test extends advanced_testcase {
    use test_data_definition;

    /**
     * Sample file path
     */
    public const SAMPLE_FILE_PATH = '/mod/competvet/tests/fixtures/importer/sample_planning_upload.csv';

    /**
     * Setup the test
     *
     * @param array $data The data definition to prepare for the test.
     * @return void
     */
    public function prepare(array $data): void {
        $this->generates_definition(
            $data,
            $this->getDataGenerator(),
            $this->getDataGenerator()->get_plugin_generator('mod_competvet')
        );
    }

    /**
     * Test import planning sans planning existant.
     */
    public function test_import_planning_no_existing(): void {
        global $CFG;
        $this->resetAfterTest();
        $data = [
            'course 1' => [
                'users' => [
                    'student' => ['student1', 'student2'],
                    'manager' => ['manager'],
                ],
                'groups' => [
                    'Group1' => [
                        'users' => ['student1'],
                    ],
                    'Group2' => [
                        'users' => ['student2'],
                    ],
                    'Group3' => [
                        'users' => ['student2'],
                    ],
                    'Group4' => [
                        'users' => [],
                    ],
                    'Group5' => [
                        'users' => [],
                    ],
                ],
                'activities' => [
                    'SIT1' => [
                        'category' => 'Y1',
                    ],
                ],
            ],
        ];
        $this->prepare($data);
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $competvet = competvet::get_from_situation($situation);
        $this->assertEquals(0, planning::count_records(['situationid' => $situation->get('id')]));

        $importer = new planning_importer(planning::class, $competvet->get_course_id(), $situation->get('id'));
        $importer->import($CFG->dirroot . self::SAMPLE_FILE_PATH);
        $plannings = array_values(planning::get_records(['situationid' => $situation->get('id')]));
        $this->assertCount(5, $plannings, 'Number of plannings should be 4 after import.');
        $this->assertEquals(10, planning_pause::count_records());
        $this->assertEquals(2, planning_pause::count_records(['planningid' => $plannings[0]->get('id')]));
        $this->assertEquals(2, planning_pause::count_records(['planningid' => $plannings[1]->get('id')]));
        $this->assertEquals(4, planning_pause::count_records(['planningid' => $plannings[3]->get('id')]));

        // Now check that we round the start and end date correctly, even for the pauses.
        $planning5 = planning::get_record(['situationid' => $situation->get('id'), 'session' => 'session-5']);

        $this->assertEquals(
            '00:00',
            \core_date::strftime('%H:%M', $planning5->get('startdate')),
        );
        $this->assertEquals(
            '23:59',
            \core_date::strftime('%H:%M', $planning5->get('enddate')),
        );

        $expected = [
            ['00:00', '23:59'],
            ['00:00', '23:00'],
        ];
        $pauses = array_values(planning_pause::get_records(['planningid' => $planning5->get('id')]));
        $this->assertCount(count($expected), $pauses, 'There should be 2 pauses for planning 5.');
        foreach ($pauses as $pause) {
            [$expectedstart, $expectedend] = array_shift($expected);
            $this->assertEquals(
                $expectedstart,
                \core_date::strftime('%H:%M', $pause->get('startdate')),
            );
            $this->assertEquals(
                $expectedend,
                \core_date::strftime('%H:%M', $pause->get('enddate')),
            );
        }
    }

    /**
     * Test import planning with dates containing seconds (Excel export format).
     */
    public function test_import_planning_with_seconds_in_dates(): void {
        global $CFG;
        $this->resetAfterTest();
        $data = [
            'course 1' => [
                'users' => [
                    'student' => ['student1'],
                    'manager' => ['manager'],
                ],
                'groups' => [
                    'Group1' => [
                        'users' => ['student1'],
                    ],
                ],
                'activities' => [
                    'SIT1' => [
                        'category' => 'Y1',
                    ],
                ],
            ],
        ];
        $this->prepare($data);
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $competvet = competvet::get_from_situation($situation);

        // Create a temporary CSV with seconds in the date (Excel export format).
        $csvcontent = "Group Name;Start Date;End Date;Session;Pause_start_1;Pause_end_1\n"
            . "Group1;01/09/2024 00:00:00;07/09/2024 23:00:00;session-seconds;02/09/2024 00:00:00;03/09/2024 23:00:00\n";
        $tmpfile = tempnam($CFG->tempdir, 'planning_test_');
        file_put_contents($tmpfile, $csvcontent);

        $importer = new planning_importer(planning::class, $competvet->get_course_id(), $situation->get('id'));
        $importer->import($tmpfile);
        unlink($tmpfile);

        $planning = planning::get_record(['situationid' => $situation->get('id'), 'session' => 'session-seconds']);
        $this->assertNotFalse($planning, 'Planning with seconds-format dates should import successfully.');
        $this->assertEquals(
            '00:00',
            \core_date::strftime('%H:%M', $planning->get('startdate')),
        );
        $this->assertEquals(
            '23:00',
            \core_date::strftime('%H:%M', $planning->get('enddate')),
        );

        // Verify pause dates were also parsed correctly with seconds.
        $pauses = planning_pause::get_records(['planningid' => $planning->get('id')]);
        $this->assertCount(1, $pauses);
        $pause = reset($pauses);
        $this->assertEquals(
            '00:00',
            \core_date::strftime('%H:%M', $pause->get('startdate')),
        );
        $this->assertEquals(
            '23:00',
            \core_date::strftime('%H:%M', $pause->get('enddate')),
        );
    }

    /**
     * Test import planning avec planning existant (mise à jour).
     */
    public function test_import_planning_with_existing(): void {
        global $CFG;
        $this->resetAfterTest();
        $clock = $this->mock_clock_with_frozen();
        $startdate = $clock->time();
        $oneweek = 7 * DAYSECS;
        $onemonth = 30 * DAYSECS;
        $data = [
            'course 1' => [
                'users' => [
                    'student' => ['student1', 'student2'],
                    'manager' => ['manager'],
                ],
                'groups' => [
                    'Group1' => [
                        'users' => ['student1'],
                    ],
                    'Group2' => [
                        'users' => ['student2'],
                    ],
                    'Group3' => [
                        'users' => ['student2'],
                    ],
                    'Group4' => [
                        'users' => [],
                    ],
                    'Group5' => [
                        'users' => [],
                    ],
                ],
                'activities' => [
                    'SIT1' => [
                        'category' => 'Y1',
                        'plannings' => [
                            [
                                'startdate' => $startdate,
                                'enddate' => $startdate + $oneweek,
                                'groupname' => 'Group1',
                                'session' => 'session2023',
                            ],
                            [
                                'startdate' => $startdate + $oneweek,
                                'enddate' => $startdate + $oneweek * 2,
                                'groupname' => 'Group3',
                                'session' => 'session2024',
                            ],
                            [
                                'startdate' => $startdate + $onemonth * 12, // Future time.
                                'enddate' => $startdate + $onemonth * 12 + $oneweek,
                                'groupname' => 'Group2',
                                'session' => 'session2025',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $this->prepare($data);
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $competvet = competvet::get_from_situation($situation);
        // First import.
        $importer = new planning_importer(planning::class, $competvet->get_course_id(), $situation->get('id'));
        $importer->import($CFG->dirroot . self::SAMPLE_FILE_PATH);
        $firstimport = planning::get_records(['situationid' => $situation->get('id')]);
        $this->assertEquals(8, count($firstimport));
        // Second import (même fichier, doit mettre à jour ou ignorer les doublons).
        $importer->import($CFG->dirroot . self::SAMPLE_FILE_PATH);
        $secondimport = planning::get_records(['situationid' => $situation->get('id')]);
        $this->assertEquals(count($firstimport), count($secondimport));
        // Third import: Here there is a potential improvement to achieve: if the planning/situation/course are the same, but
        // we change the date, maybe we should update the planning instead of creating a new one. This is discussed in
        // https://helplearning.planio.com/issues/682.
        $lastplanning = planning::get_record(['situationid' => $situation->get('id'), 'session' => 'session-4']);
        $lastplanning->set('startdate', $startdate + $onemonth * 12);
        $lastplanning->save();
        $importer->import($CFG->dirroot . self::SAMPLE_FILE_PATH);
        $thirdimport = planning::get_records(['situationid' => $situation->get('id')]);
        $this->assertEquals(count($firstimport) + 1, count($thirdimport));
    }
}
