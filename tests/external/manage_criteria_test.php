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

namespace mod_competvet\external;

use mod_competvet\competvet;

/**
 * Manage criteria tests
 *
 * @package     mod_competvet
 * @copyright   2023 CALL Learning <contact@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \mod_competvet\external\manage_criteria
 */
final class manage_criteria_test extends \advanced_testcase {
    /**
     * Test creation and retrieval of grid and criteria
     * @runInSeparateProcess
     */
    public function test_create_and_get_grid_and_criteria(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $competvetgenerator = $this->getDataGenerator()->get_plugin_generator('mod_competvet');
        $competvet = $competvetgenerator->create_instance(['course' => $course->id]);
        $grid = new \mod_competvet\local\persistent\grid(0, (object) [
            'name' => 'Test Grid',
            'idnumber' => 'GRID001',
            'type' => \mod_competvet\local\persistent\grid::COMPETVET_CRITERIA_EVALUATION,
        ]);
        $grid->create();
        $criterion = new \mod_competvet\local\persistent\criterion(0, (object) [
            'gridid' => $grid->get('id'),
            'label' => 'Test Criterion',
            'idnumber' => 'CRIT001',
            'parentid' => 0,
            'sort' => 1,
        ]);
        $criterion->create();
        $this->setAdminUser();
        $result = $this->manage_criteria_get(
            \mod_competvet\local\persistent\grid::COMPETVET_CRITERIA_EVALUATION,
            $grid->get('id'),
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('grids', $result);
        $this->assertCount(1, $result['grids']);
        $this->assertEquals('Test Grid', $result['grids'][0]['gridname']);
        $this->assertTrue($result['grids'][0]['canduplicate']);
        $this->assertCount(1, $result['grids'][0]['criteria']);
        $this->assertEquals('Test Criterion', $result['grids'][0]['criteria'][0]['label']);
    }

    /**
     * A restricted system role can manage global grids without being a site admin.
     */
    /**
     * Test restricted global-grid access in isolation.
     *
     * @runInSeparateProcess
     */
    public function test_restricted_global_grid_manager_access(): void {
        $this->resetAfterTest();
        $grid = new \mod_competvet\local\persistent\grid(0, (object) [
            'name' => 'Restricted grid', 'idnumber' => 'GRID011',
            'type' => \mod_competvet\local\persistent\grid::COMPETVET_CRITERIA_EVALUATION,
        ]);
        $grid->create();

        $roleid = create_role('Global grid manager', 'globalgridmanager', 'Manage global CompetVet grids');
        assign_capability(
            'mod/competvet:manageglobalcriteria',
            CAP_ALLOW,
            $roleid,
            \context_system::instance()->id
        );
        $user = $this->getDataGenerator()->create_user();
        role_assign($roleid, $user->id, \context_system::instance()->id);
        $this->setUser($user);

        $result = $this->manage_criteria_get(
            \mod_competvet\local\persistent\grid::COMPETVET_CRITERIA_EVALUATION,
            $grid->get('id')
        );
        $this->assertCount(1, $result['grids']);
        $this->assertTrue($result['grids'][0]['canedit']);

        $criterion = new \mod_competvet\local\persistent\criterion(0, (object) [
            'gridid' => $grid->get('id'),
            'label' => 'Restricted criterion',
            'idnumber' => 'CRIT011',
            'parentid' => 0,
            'sort' => 1,
        ]);
        $criterion->create();

        $result = $this->manage_criteria_update([
            [
                'gridid' => $grid->get('id'),
                'gridname' => 'Restricted grid updated',
                'type' => $grid->get('type'),
                'haschanged' => true,
                'criteria' => [
                    [
                        'criterionid' => $criterion->get('id'),
                        'label' => 'Restricted criterion updated',
                        'idnumber' => 'CRIT011',
                        'sortorder' => 1,
                        'haschanged' => true,
                        'hasoptions' => false,
                        'options' => [],
                    ],
                ],
            ],
        ], $grid->get('type'));
        $this->assertTrue($result['result']);

        $result = $this->manage_criteria_update([
            [
                'gridid' => $grid->get('id'),
                'type' => $grid->get('type'),
                'criteria' => [
                    [
                        'criterionid' => $criterion->get('id'),
                        'label' => 'Restricted criterion updated',
                        'idnumber' => 'CRIT011',
                        'sortorder' => 1,
                        'deleted' => true,
                        'hasoptions' => false,
                        'options' => [],
                    ],
                ],
            ],
        ], $grid->get('type'));
        $this->assertTrue($result['result']);

        $result = $this->manage_criteria_update([
            [
                'gridid' => $grid->get('id'),
                'type' => $grid->get('type'),
                'deleted' => true,
                'criteria' => [],
            ],
        ], $grid->get('type'));
        $this->assertTrue($result['result']);

        // I can still get the criteria.
        $this->setUser($this->getDataGenerator()->create_user());
        $criteria = $this->manage_criteria_get(
            \mod_competvet\local\persistent\grid::COMPETVET_CRITERIA_EVALUATION,
            $grid->get('id')
        );
        $this->assertNotEmpty($criteria);
    }

    /**
     * Test update of grid and criterion
     * @runInSeparateProcess
     */
    public function test_update_grid_and_criterion(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $competvetgenerator = $this->getDataGenerator()->get_plugin_generator('mod_competvet');
        $competvet = $competvetgenerator->create_instance(['course' => $course->id]);
        $grid = new \mod_competvet\local\persistent\grid(0, (object) [
            'name' => 'Grid to Update',
            'idnumber' => 'GRID002',
            'type' => \mod_competvet\local\persistent\grid::COMPETVET_CRITERIA_EVALUATION,
        ]);
        $grid->create();
        $criterion = new \mod_competvet\local\persistent\criterion(0, (object) [
            'gridid' => $grid->get('id'),
            'label' => 'Criterion to Update',
            'idnumber' => 'CRIT002',
            'parentid' => 0,
            'sort' => 1,
        ]);
        $criterion->create();
        $updateparams = [
            [
                'gridid' => $grid->get('id'),
                'gridname' => 'Grid Updated',
                'type' => $grid->get('type'),
                'haschanged' => true,
                'criteria' => [
                    [
                        'criterionid' => $criterion->get('id'),
                        'label' => 'Criterion Updated',
                        'idnumber' => 'CRIT002',
                        'sortorder' => 1,
                        'haschanged' => true,
                        'hasoptions' => false,
                        'options' => [],
                    ],
                ],
            ],
        ];
        $this->setAdminUser();
        $result = $this->manage_criteria_update($updateparams, $grid->get('type'));
        $this->assertTrue($result['result']);
        $getresult = $this->manage_criteria_get($grid->get('type'), $grid->get('id'), null);
        $this->assertEquals('Grid Updated', $getresult['grids'][0]['gridname']);
        $this->assertEquals('Criterion Updated', $getresult['grids'][0]['criteria'][0]['label']);
    }

    /**
     * New criteria can be included in the same request as a reorder.
     */
    /**
     * Test criterion reordering in isolation.
     *
     * @runInSeparateProcess
     */
    public function test_reorder_with_new_criterion(): void {
        $this->resetAfterTest();
        $grid = new \mod_competvet\local\persistent\grid(0, (object) [
            'name' => 'Grid with new criterion', 'idnumber' => 'GRID010',
            'type' => \mod_competvet\local\persistent\grid::COMPETVET_CRITERIA_EVALUATION,
        ]);
        $grid->create();
        $criterion = new \mod_competvet\local\persistent\criterion(0, (object) [
            'gridid' => $grid->get('id'), 'label' => 'Existing', 'idnumber' => 'CRIT010',
            'parentid' => 0, 'sort' => 1,
        ]);
        $criterion->create();

        $this->setAdminUser();
        $result = $this->manage_criteria_update([
            [
                'gridid' => $grid->get('id'), 'type' => $grid->get('type'), 'updatesortorder' => true,
                'criteria' => [
                    [
                        'criterionid' => 0, 'label' => 'New', 'idnumber' => 'CRIT011', 'sortorder' => 1,
                        'haschanged' => true, 'hasoptions' => false, 'options' => [],
                    ],
                    [
                        'criterionid' => $criterion->get('id'), 'label' => 'Existing', 'idnumber' => 'CRIT010',
                        'sortorder' => 2, 'options' => [],
                    ],
                ],
            ],
        ], $grid->get('type'));

        $this->assertTrue($result['result']);
        $criteria = $this->manage_criteria_get($grid->get('type'), $grid->get('id'))['grids'][0]['criteria'];
        $this->assertSame('New', $criteria[0]['label']);
        $this->assertSame('Existing', $criteria[1]['label']);
    }

    /**
     * Test deletion of grid using deleted field
     * @runInSeparateProcess
     */
    public function test_delete_grid(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $competvetgenerator = $this->getDataGenerator()->get_plugin_generator('mod_competvet');
        $competvet = $competvetgenerator->create_instance(['course' => $course->id]);
        $grid = new \mod_competvet\local\persistent\grid(0, (object) [
            'name' => 'Grid to Delete',
            'idnumber' => 'GRID003',
            'type' => \mod_competvet\local\persistent\grid::COMPETVET_CRITERIA_EVALUATION,
        ]);
        $grid->create();
        $deleteparams = [
            [
                'gridid' => $grid->get('id'),
                'type' => $grid->get('type'),
                'deleted' => true,
                'criteria' => [],
            ],
        ];
        $this->setAdminUser();
        $result = $this->manage_criteria_update($deleteparams, $grid->get('type'));
        $this->assertTrue($result['result']);
        $getresult = $this->manage_criteria_get($grid->get('type'), $grid->get('id'), null);
        $this->assertCount(0, $getresult['grids']);
    }

    /**
     * Test deletion of criterion using deleted field
     * @runInSeparateProcess
     */
    public function test_delete_criterion(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $competvetgenerator = $this->getDataGenerator()->get_plugin_generator('mod_competvet');
        $competvet = $competvetgenerator->create_instance(['course' => $course->id]);
        $grid = new \mod_competvet\local\persistent\grid(0, (object) [
            'name' => 'Grid for Criterion Delete',
            'idnumber' => 'GRID004',
            'type' => \mod_competvet\local\persistent\grid::COMPETVET_CRITERIA_EVALUATION,
        ]);
        $grid->create();
        $criterion = new \mod_competvet\local\persistent\criterion(0, (object) [
            'gridid' => $grid->get('id'),
            'label' => 'Criterion to Delete',
            'idnumber' => 'CRIT004',
            'parentid' => 0,
            'sort' => 1,
        ]);
        $criterion->create();
        $deleteparams = [
            [
                'gridid' => $grid->get('id'),
                'type' => $grid->get('type'),
                'criteria' => [
                    [
                        'criterionid' => $criterion->get('id'),
                        'label' => 'Criterion to Delete',
                        'idnumber' => 'CRIT004',
                        'sortorder' => 1,
                        'deleted' => true,
                        'hasoptions' => false,
                        'options' => [],
                    ],
                ],
            ],
        ];
        $this->setAdminUser();
        $result = $this->manage_criteria_update($deleteparams, $grid->get('type'));
        $this->assertTrue($result['result']);
        $getresult = $this->manage_criteria_get($grid->get('type'), $grid->get('id'), null);
        $this->assertCount(0, $getresult['grids'][0]['criteria']);
    }

    /**
     * Test deletion of option using deleted field
     * @runInSeparateProcess
     */
    public function test_delete_option(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $competvetgenerator = $this->getDataGenerator()->get_plugin_generator('mod_competvet');
        $competvet = $competvetgenerator->create_instance(['course' => $course->id]);
        $grid = new \mod_competvet\local\persistent\grid(0, (object) [
            'name' => 'Grid for Option Delete',
            'idnumber' => 'GRID005',
            'type' => \mod_competvet\local\persistent\grid::COMPETVET_CRITERIA_EVALUATION,
        ]);
        $grid->create();
        $criterion = new \mod_competvet\local\persistent\criterion(0, (object) [
            'gridid' => $grid->get('id'),
            'label' => 'Criterion with Option',
            'idnumber' => 'CRIT005',
            'parentid' => 0,
            'sort' => 1,
        ]);
        $criterion->create();
        $option1 = new \mod_competvet\local\persistent\criterion(0, (object) [
            'gridid' => $grid->get('id'),
            'label' => 'Option to Delete',
            'idnumber' => 'OPT005',
            'parentid' => $criterion->get('id'),
            'sort' => 1,
        ]);
        $option1->create();
        $option2 = new \mod_competvet\local\persistent\criterion(0, (object) [
            'gridid' => $grid->get('id'),
            'label' => 'Option that we cannot delete because it used',
            'idnumber' => 'OPT006',
            'parentid' => $criterion->get('id'),
            'sort' => 2,
        ]);
        $option2->create();

        // Fake usage of the option in a situation to test that it cannot be deleted.
        $competvetinstance = competvet::get_from_cmid($competvet->cmid);
        $observation = new \mod_competvet\local\persistent\observation_criterion_level(
            0,
            (object) [
                'situationid' => $competvetinstance->get_situation()->get('id'),
                'criterionid' => $option2->get('id'),
                'observationid' => 0, // Fake one.
                'level' => 1,
            ]
        );
        $observation->create();
        $deleteparams = [
            [
                'gridid' => $grid->get('id'),
                'type' => $grid->get('type'),
                'criteria' => [
                    [
                        'criterionid' => $criterion->get('id'),
                        'label' => 'Criterion with Option',
                        'idnumber' => 'CRIT005',
                        'sortorder' => 1,
                        'hasoptions' => true,
                        'haschanged' => true,
                        'options' => [
                            [
                                'optionid' => $option1->get('id'),
                                'idnumber' => 'OPT005',
                                'label' => 'Option to Delete',
                                'sortorder' => 1,
                                'deleted' => true,
                            ],
                        ],
                    ],
                ],
            ],
        ];
        // Run the test. First try to delete the option that is not used in a situation, it should be deleted without warning.
        $this->setAdminUser();
        $grids = $this->manage_criteria_get($grid->get('type'), $grid->get('id'), null);
        $this->assertCount(2, $grids['grids'][0]['criteria'][0]['options']);
        $result = $this->manage_criteria_update($deleteparams, $grid->get('type'));
        $this->assertTrue($result['result']);
        $this->assertEmpty($result['warnings']);
        $grids = $this->manage_criteria_get($grid->get('type'), $grid->get('id'), null);
        $this->assertCount(1, $grids['grids'][0]['criteria'][0]['options']);
        // Now try to delete the option that is used in a situation, it should not be deleted and we should get a warning.
        $deleteparams[0]['criteria'][0]['options'][0]['optionid'] = $option2->get('id');
        $deleteparams[0]['criteria'][0]['options'][0]['deleted'] = true;
        $result = $this->manage_criteria_update($deleteparams, $grid->get('type'));
        $this->assertTrue($result['result']);
        $this->assertNotEmpty($result['warnings']);
        $grids = $this->manage_criteria_get($grid->get('type'), $grid->get('id'), null);
        $this->assertCount(1, $grids['grids'][0]['criteria']);
    }

    /**
     * Test error handling for deleting non-existent criterion
     * @runInSeparateProcess
     */
    public function test_delete_nonexistent_criterion(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $competvetgenerator = $this->getDataGenerator()->get_plugin_generator('mod_competvet');
        $competvet = $competvetgenerator->create_instance(['course' => $course->id]);
        $grid = new \mod_competvet\local\persistent\grid(0, (object) [
            'name' => 'Grid for Error Test',
            'idnumber' => 'GRID006',
            'type' => \mod_competvet\local\persistent\grid::COMPETVET_CRITERIA_EVALUATION,
        ]);
        $grid->create();
        $deleteparams = [
            [
                'gridid' => $grid->get('id'),
                'type' => $grid->get('type'),
                'criteria' => [
                    [
                        'criterionid' => 999999,
                        'label' => 'Nonexistent Criterion',
                        'idnumber' => 'CRIT999',
                        'sortorder' => 1,
                        'deleted' => true,
                        'hasoptions' => false,
                        'options' => [],
                    ],
                ],
            ],
        ];
        $this->setAdminUser();
        $result = $this->manage_criteria_update($deleteparams, $grid->get('type'));
        $this->assertTrue($result['result']);
        $this->assertNotEmpty($result['warnings']);

        // Test with non-admin user.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $this->expectException(\moodle_exception::class);
        $this->manage_criteria_update($deleteparams, $grid->get('type'));
    }

    /**
     * Structural edits must not move criteria between grids.
     */
    /**
     * Test unsafe criterion changes in isolation.
     *
     * @runInSeparateProcess
     */
    public function test_reject_unsafe_criterion_structure_changes(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $grid1 = new \mod_competvet\local\persistent\grid(0, (object) [
            'name' => 'Grid one', 'idnumber' => 'GRID007',
            'type' => \mod_competvet\local\persistent\grid::COMPETVET_CRITERIA_EVALUATION,
        ]);
        $grid1->create();
        $grid2 = new \mod_competvet\local\persistent\grid(0, (object) [
            'name' => 'Grid two', 'idnumber' => 'GRID008',
            'type' => \mod_competvet\local\persistent\grid::COMPETVET_CRITERIA_EVALUATION,
        ]);
        $grid2->create();
        $criterion = new \mod_competvet\local\persistent\criterion(0, (object) [
            'gridid' => $grid1->get('id'), 'label' => 'Criterion', 'idnumber' => 'CRIT007',
            'parentid' => 0, 'sort' => 1,
        ]);
        $criterion->create();

        $this->expectException(\moodle_exception::class);
        \mod_competvet\local\api\criteria::update_criterion(
            $criterion->get('id'),
            'Criterion',
            'CRIT007',
            1,
            $grid2->get('id'),
            0,
            null
        );
    }

    /**
     * A parent cannot be removed while one of its used options remains.
     */
    /**
     * Test deleting a parent criterion in isolation.
     *
     * @runInSeparateProcess
     */
    public function test_delete_parent_preserves_used_children(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $grid = new \mod_competvet\local\persistent\grid(0, (object) [
            'name' => 'Grid for parent delete', 'idnumber' => 'GRID009',
            'type' => \mod_competvet\local\persistent\grid::COMPETVET_CRITERIA_EVALUATION,
        ]);
        $grid->create();
        $parent = new \mod_competvet\local\persistent\criterion(0, (object) [
            'gridid' => $grid->get('id'), 'label' => 'Parent', 'idnumber' => 'CRIT009',
            'parentid' => 0, 'sort' => 1,
        ]);
        $parent->create();
        $option = new \mod_competvet\local\persistent\criterion(0, (object) [
            'gridid' => $grid->get('id'), 'label' => 'Used option', 'idnumber' => 'OPT009',
            'parentid' => $parent->get('id'), 'sort' => 1,
        ]);
        $option->create();
        $DB->insert_record('competvet_obs_crit_level', (object) [
            'situationid' => 0, 'observationid' => 0, 'criterionid' => $option->get('id'), 'level' => 1,
        ]);

        $this->assertFalse(\mod_competvet\local\api\criteria::delete_criterion($parent->get('id')));
        $this->assertTrue($DB->record_exists('competvet_criterion', ['id' => $parent->get('id')]));
        $this->assertTrue($DB->record_exists('competvet_criterion', ['id' => $option->get('id')]));
    }

    /**
     * A new criterion with criterionid=0 and haschanged=false must not break sort-order updates.
     */
    /**
     * Test criterion reordering without haschanged in isolation.
     *
     * @runInSeparateProcess
     */
    public function test_reorder_with_new_criterion_without_haschanged(): void {
        $this->resetAfterTest();
        $grid = new \mod_competvet\local\persistent\grid(0, (object) [
            'name' => 'Grid for reorder edge case', 'idnumber' => 'GRID012',
            'type' => \mod_competvet\local\persistent\grid::COMPETVET_CRITERIA_EVALUATION,
        ]);
        $grid->create();
        $criterion = new \mod_competvet\local\persistent\criterion(0, (object) [
            'gridid' => $grid->get('id'), 'label' => 'Existing', 'idnumber' => 'CRIT012',
            'parentid' => 0, 'sort' => 1,
        ]);
        $criterion->create();

        $this->setAdminUser();
        // Simulate frontend sending a new criterion with criterionid=0 and haschanged=false
        // alongside a reorder request. This used to cause an exception because 0 was added
        // to the sort-order array.
        $result = $this->manage_criteria_update([
            [
                'gridid' => $grid->get('id'), 'type' => $grid->get('type'), 'updatesortorder' => true,
                'criteria' => [
                    [
                        'criterionid' => 0, 'label' => 'New (unchanged)', 'idnumber' => 'CRIT013',
                        'sortorder' => 1, 'haschanged' => false, 'hasoptions' => false, 'options' => [],
                    ],
                    [
                        'criterionid' => $criterion->get('id'), 'label' => 'Existing', 'idnumber' => 'CRIT012',
                        'sortorder' => 2, 'options' => [],
                    ],
                ],
            ],
        ], $grid->get('type'));

        $this->assertTrue($result['result'], 'Reorder with new criterion (no haschanged) should not throw.');
        $criteria = $this->manage_criteria_get($grid->get('type'), $grid->get('id'))['grids'][0]['criteria'];
        // Only the existing criterion should appear (the new one was not created because haschanged=false).
        $this->assertCount(1, $criteria);
        $this->assertSame('Existing', $criteria[0]['label']);
    }

    /**
     * Duplicating a global grid returns the new grid id and copies the structure.
     *
     * @runInSeparateProcess
     */
    public function test_duplicate_grid_success(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $grid = new \mod_competvet\local\persistent\grid(0, (object) [
            'name' => 'Source grid', 'idnumber' => 'GRID200',
            'type' => \mod_competvet\local\persistent\grid::COMPETVET_CRITERIA_EVALUATION,
        ]);
        $grid->create();
        $parent = new \mod_competvet\local\persistent\criterion(0, (object) [
            'gridid' => $grid->get('id'), 'label' => 'Parent', 'idnumber' => 'CRIT200',
            'parentid' => 0, 'sort' => 1,
        ]);
        $parent->create();
        $option = new \mod_competvet\local\persistent\criterion(0, (object) [
            'gridid' => $grid->get('id'), 'label' => 'Option', 'idnumber' => 'OPT200',
            'parentid' => $parent->get('id'), 'sort' => 1,
        ]);
        $option->create();

        $sourcecriterioncount = \mod_competvet\local\persistent\criterion::count_records(['gridid' => $grid->get('id')]);

        $result = $this->manage_criteria_duplicate_grid($grid->get('id'));
        $newgridid = $result['newgridid'];

        $this->assertNotEquals($grid->get('id'), $newgridid);
        $newgrid = \mod_competvet\local\persistent\grid::get_record(['id' => $newgridid]);
        $this->assertNotNull($newgrid);
        $this->assertEquals(
            'Source grid' . get_string('copysuffix', 'mod_competvet'),
            $newgrid->get('name')
        );
        // The copy keeps the scope, type and sortorder of the source.
        $this->assertEquals($grid->get('situationid'), $newgrid->get('situationid'));
        $this->assertEquals($grid->get('type'), $newgrid->get('type'));
        $this->assertEquals($grid->get('sortorder'), $newgrid->get('sortorder'));
        // All criteria are copied and the source is untouched.
        $this->assertEquals(
            $sourcecriterioncount,
            \mod_competvet\local\persistent\criterion::count_records(['gridid' => $newgridid])
        );
        $this->assertEquals(
            $sourcecriterioncount,
            \mod_competvet\local\persistent\criterion::count_records(['gridid' => $grid->get('id')])
        );
        // The source grid name is unchanged.
        $this->assertEquals(
            'Source grid',
            \mod_competvet\local\persistent\grid::get_record(['id' => $grid->get('id')])->get('name')
        );
    }

    /**
     * Duplicating a missing grid throws invaliddata.
     *
     * @runInSeparateProcess
     */
    public function test_duplicate_grid_missing_grid(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage('Invalid data');
        $this->manage_criteria_duplicate_grid(999999);
    }

    /**
     * A user without the global scope capability cannot duplicate a global grid.
     *
     * @runInSeparateProcess
     */
    public function test_duplicate_grid_noaccess_global_grid(): void {
        $this->resetAfterTest();

        $grid = new \mod_competvet\local\persistent\grid(0, (object) [
            'name' => 'Global grid', 'idnumber' => 'GRID201',
            'type' => \mod_competvet\local\persistent\grid::COMPETVET_CRITERIA_EVALUATION,
        ]);
        $grid->create();

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage('You don\'t have access');
        $this->manage_criteria_duplicate_grid($grid->get('id'));
    }

    /**
     * A user without the activity capability cannot duplicate a situation grid.
     *
     * @runInSeparateProcess
     */
    public function test_duplicate_grid_noaccess_situation_grid(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $competvetgenerator = $this->getDataGenerator()->get_plugin_generator('mod_competvet');
        $competvet = $competvetgenerator->create_instance(['course' => $course->id]);
        $situation = \mod_competvet\local\persistent\situation::get_record(['competvetid' => $competvet->id]);
        $grid = new \mod_competvet\local\persistent\grid(0, (object) [
            'name' => 'Situation grid', 'idnumber' => 'GRID202',
            'type' => \mod_competvet\local\persistent\grid::COMPETVET_CRITERIA_EVALUATION,
            'situationid' => $situation->get('id'),
        ]);
        $grid->create();

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage('You don\'t have access');
        $this->manage_criteria_duplicate_grid($grid->get('id'));
    }

    /**
     * The duplicate_grid webservice is registered in the external_functions table.
     *
     * @runInSeparateProcess
     */
    public function test_duplicate_grid_webservice_is_registered(): void {
        global $DB, $CFG;
        $this->resetAfterTest();
        $this->setAdminUser();

        // Re-sync the external_functions table from db/services.php (the test database is only
        // reinstalled from the plugin when the plugin is (re)installed, not on every test run).
        require_once($CFG->libdir . '/upgradelib.php');
        external_update_descriptions('mod_competvet');

        $function = $DB->get_record('external_functions', ['name' => 'mod_competvet_duplicate_grid']);
        $this->assertNotNull($function, 'mod_competvet_duplicate_grid should be registered.');
        $this->assertEquals(\mod_competvet\external\manage_criteria::class, $function->classname);
        $this->assertEquals('duplicate_grid', $function->methodname);
        $this->assertEquals('mod_competvet', $function->component);
    }

    /**
     * The grid a situation actually uses is returned by get even when it is a global grid.
     *
     * @runInSeparateProcess
     */
    public function test_get_includes_situation_inuse_global_grid(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $competvetgenerator = $this->getDataGenerator()->get_plugin_generator('mod_competvet');
        $globalgrid = new \mod_competvet\local\persistent\grid(0, (object) [
            'name' => 'Global in-use grid', 'idnumber' => 'GRID300',
            'type' => \mod_competvet\local\persistent\grid::COMPETVET_CRITERIA_EVALUATION,
        ]);
        $globalgrid->create();
        $competvet = $competvetgenerator->create_instance(['course' => $course->id, 'evalgrid' => $globalgrid->get('id')]);
        $situation = \mod_competvet\local\persistent\situation::get_record(['competvetid' => $competvet->id]);
        $this->assertEquals($globalgrid->get('id'), $situation->get('evalgrid'));

        $this->setAdminUser();
        $result = $this->manage_criteria_get(
            \mod_competvet\local\persistent\grid::COMPETVET_CRITERIA_EVALUATION,
            0,
            $situation->get('id')
        );
        // The situation has no grid of its own, but the global grid it uses must be returned.
        $this->assertCount(1, $result['grids']);
        $this->assertEquals($globalgrid->get('id'), $result['grids'][0]['gridid']);
    }

    /**
     * The grid in use of a situation is not duplicated when it is already scoped to that situation.
     *
     * @runInSeparateProcess
     */
    public function test_get_inuse_grid_not_duplicated(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $competvetgenerator = $this->getDataGenerator()->get_plugin_generator('mod_competvet');
        $competvet = $competvetgenerator->create_instance(['course' => $course->id]);
        $situation = \mod_competvet\local\persistent\situation::get_record(['competvetid' => $competvet->id]);
        $grid = new \mod_competvet\local\persistent\grid(0, (object) [
            'name' => 'Situation grid', 'idnumber' => 'GRID301',
            'type' => \mod_competvet\local\persistent\grid::COMPETVET_CRITERIA_EVALUATION,
            'situationid' => $situation->get('id'),
        ]);
        $grid->create();
        $situation->set('evalgrid', $grid->get('id'));
        $situation->update();

        $this->setAdminUser();
        $result = $this->manage_criteria_get(
            \mod_competvet\local\persistent\grid::COMPETVET_CRITERIA_EVALUATION,
            0,
            $situation->get('id')
        );
        $this->assertCount(1, $result['grids']);
        $this->assertEquals($grid->get('id'), $result['grids'][0]['gridid']);
    }

    /**
     * Creating a new grid with a situation id through the webservice makes the situation use that grid.
     *
     * @runInSeparateProcess
     */
    public function test_update_new_grid_assigns_situation(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $competvetgenerator = $this->getDataGenerator()->get_plugin_generator('mod_competvet');
        $competvet = $competvetgenerator->create_instance(['course' => $course->id]);
        $situation = \mod_competvet\local\persistent\situation::get_record(['competvetid' => $competvet->id]);

        $this->setAdminUser();
        $result = $this->manage_criteria_update([
            [
                'gridid' => 0,
                'gridname' => 'New situation grid',
                'type' => \mod_competvet\local\persistent\grid::COMPETVET_CRITERIA_EVALUATION,
                'situationid' => $situation->get('id'),
                'haschanged' => true,
                'criteria' => [],
            ],
        ], \mod_competvet\local\persistent\grid::COMPETVET_CRITERIA_EVALUATION);
        $this->assertTrue($result['result']);

        $situation = \mod_competvet\local\persistent\situation::get_record(['competvetid' => $competvet->id]);
        $newgrid = \mod_competvet\local\persistent\grid::get_record(['situationid' => $situation->get('id')]);
        $this->assertNotNull($newgrid);
        $this->assertEquals($newgrid->get('id'), $situation->get('evalgrid'));
    }

    /**
     * Helper for manage_criteria::update
     *
     * @param array $grids
     * @param int $type
     * @return array
     */
    protected function manage_criteria_update(array $grids, int $type) {
        $validate = [manage_criteria::class, 'validate_parameters'];
        $params = call_user_func(
            $validate,
            manage_criteria::update_parameters(),
            ['grids' => $grids, 'type' => $type]
        );
        $params = array_values($params);
        $returnvalue = manage_criteria::update(...$params);
        return \external_api::clean_returnvalue(manage_criteria::update_returns(), $returnvalue);
    }

    /**
     * Helper for manage_criteria::get
     *
     * @param int $type
     * @param int $gridid
     * @param int|null $situationid
     * @return array
     */
    protected function manage_criteria_get(int $type, int $gridid, ?int $situationid = null) {
        $validate = [manage_criteria::class, 'validate_parameters'];
        $callparams = ['type' => $type, 'gridid' => $gridid];
        if ($situationid !== null) {
            $callparams['situationid'] = $situationid;
        }
        $params = call_user_func(
            $validate,
            manage_criteria::get_parameters(),
            $callparams
        );
        $params = array_values($params);
        $returnvalue = manage_criteria::get(...$params);
        return \external_api::clean_returnvalue(manage_criteria::get_returns(), $returnvalue);
    }

    /**
     * Helper for manage_criteria::duplicate_grid
     *
     * @param int $gridid
     * @return array
     */
    protected function manage_criteria_duplicate_grid(int $gridid) {
        $validate = [manage_criteria::class, 'validate_parameters'];
        $params = call_user_func(
            $validate,
            manage_criteria::duplicate_grid_parameters(),
            ['gridid' => $gridid]
        );
        $params = array_values($params);
        $returnvalue = manage_criteria::duplicate_grid(...$params);
        return \external_api::clean_returnvalue(manage_criteria::duplicate_grid_returns(), $returnvalue);
    }
}
