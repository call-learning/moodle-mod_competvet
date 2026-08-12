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
use mod_competvet\local\persistent\case_entry;
use mod_competvet\local\persistent\case_field;
use mod_competvet\local\persistent\case_version;
use mod_competvet\local\persistent\situation;
use mod_competvet\tests\test_data_definition;

/**
 * Case API test
 *
 * @package     mod_competvet
 * @copyright   2023 CALL Learning <contact@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\mod_competvet\local\api\cases::class)]
final class cases_test extends advanced_testcase {
    use test_data_definition;

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
        $this->set_current_date();
    }

    /**
     * Test get_entry
     *
     * @return void
     */
    public function test_get_entry(): void {
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $student = core_user::get_user_by_username('student1');
        $plannings = plannings::get_plannings_for_situation_id($situation->get('id'), $student->id);
        $planning = array_shift($plannings);
        $entries = cases::get_entries($planning['id'], $student->id);
        foreach ($entries->cases as $e) {
            $this->assertEquals($student->id, $e->studentid);
            $this->assertEquals($planning['id'], $e->planningid);
        }
        // Get the first entry and check a couple of fields.
        $firstcase = $entries->cases[0];
        $this->assertEquals(case_version::get_record(['name' => 'Legacy Caselog'])->get('id'), $firstcase->versionid);
        $this->assertEquals('validated', $firstcase->status);
        foreach ($firstcase->categories as $category) {
            $this->assertNotEmpty($category->name);
            foreach ($category->fields as $field) {
                $this->assertNotEmpty($field->name);
                switch ($field->idnumber) {
                    case 'date_cas':
                        $this->assertEquals('1 January 2021', $field->displayvalue);
                        break;
                    case 'role_charge':
                        $this->assertEquals('Observateur', $field->displayvalue);
                        break;
                    case 'reflexions_cas':
                        $this->assertEquals('Premier cas observé. Bonne prise en charge.', $field->displayvalue);
                        break;
                }
            }
        }
    }


    /**
     * Test update_case
     *
     * @return void
     */
    public function test_update_case(): void {
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $student = core_user::get_user_by_username('student1');
        $plannings = plannings::get_plannings_for_situation_id($situation->get('id'), $student->id);
        $planning = array_shift($plannings);
        $entries = cases::get_entries($planning['id'], $student->id);

        $case = $entries->cases[0];
        $caseid = $case->id;
        $datecasfield = case_field::get_record(['idnumber' => 'date_cas']);
        $rolechargefield = case_field::get_record(['idnumber' => 'role_charge']);
        $newdata = [$datecasfield->get('id') => '01 January 2023', $rolechargefield->get('id') => 2];
        // Adjust based on actual fields.

        // Perform update.
        $this->setAdminUser();
        cases::update_case($caseid, $newdata);

        $updatedcase = cases::get_entry($caseid);
        foreach ($updatedcase->categories as $category) {
            $this->assertNotEmpty($category->name);
            foreach ($category->fields as $field) {
                $this->assertNotEmpty($field->name);
                switch ($field->idnumber) {
                    case 'date_cas':
                        $this->assertEquals('1 January 2023', $field->displayvalue);
                        break;
                    case 'role_charge':
                        $this->assertEquals('Principal acteur (responsable du cas)', $field->displayvalue);
                        break;
                }
            }
        }
    }
    /**
     * Test delete_case
     *
     * @return void
     */
    public function test_delete_case(): void {
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $student = core_user::get_user_by_username('student1');
        $plannings = plannings::get_plannings_for_situation_id($situation->get('id'), $student->id);
        $planning = array_shift($plannings);
        $entries = cases::get_entries($planning['id'], $student->id);

        $case = $entries->cases[0];
        $caseid = $case->id;

        // Perform delete.
        $this->setAdminUser();
        $result = cases::delete_case($caseid);

        // Assertions.
        $this->assertTrue($result, 'Case deletion failed.');

        $this->expectException(\moodle_exception::class);
        cases::get_entry($caseid);
    }

    /**
     * Test get_case_list
     *
     * @return void
     */
    public function test_get_case_list(): void {
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $student = core_user::get_user_by_username('student1');
        $plannings = plannings::get_plannings_for_situation_id($situation->get('id'), $student->id);
        $planning = array_shift($plannings);
        $caselist = cases::get_case_list($planning['id'], $student->id);

        // Assertions.
        $this->assertNotEmpty($caselist, 'Case list is empty.');

        $expectedcases = [
            [
                'date' => 1609430400,
                'label' => 'Rex Chien',
            ],
            [
                'date' => 1686326400,
                'label' => 'Brian Oiseau',
            ],
        ];

        foreach ($caselist as $index => $case) {
            $this->assertEquals($expectedcases[$index]['date'], $case['date'], 'Case date does not match.');
            $this->assertEquals($expectedcases[$index]['label'], $case['label'], 'Case label does not match.');
        }
    }

    /** Character limits use Unicode characters and normalised line endings. */
    public function test_character_length(): void {
        $this->assertSame(3, cases::character_length("a\r\nb"));
        $this->assertSame(4, cases::character_length('éééé'));
    }

    /** Test that get_all_versions returns all Caselog form versions. */
    public function test_get_all_versions(): void {
        $versions = cases::get_all_versions();
        $this->assertNotEmpty($versions);
        // Should have at least legacy and current versions.
        $this->assertGreaterThanOrEqual(2, count($versions));
        // Each version should have the expected keys.
        foreach ($versions as $version) {
            $this->assertArrayHasKey('id', $version);
            $this->assertArrayHasKey('name', $version);
            $this->assertArrayHasKey('iscurrent', $version);
            $this->assertArrayHasKey('metadata', $version);
            $this->assertIsInt($version['id']);
            $this->assertIsString($version['name']);
            $this->assertIsBool($version['iscurrent']);
        }
        // Exactly one version should be current.
        $currentCount = array_reduce($versions, fn($c, $v) => $c + ($v['iscurrent'] ? 1 : 0), 0);
        $this->assertSame(1, $currentCount);
    }

    /** Test that get_version_structure returns structure for a specific version. */
    public function test_get_version_structure(): void {
        $current = case_version::get_current();
        $this->assertNotNull($current);
        $structure = cases::get_version_structure($current->get('id'));
        $this->assertNotEmpty($structure);
        // Should have categories with fields.
        foreach ($structure as $category) {
            $this->assertArrayHasKey('id', (array)$category);
            $this->assertArrayHasKey('name', (array)$category);
            $this->assertArrayHasKey('fields', (array)$category);
        }
    }

    /** Test that create_case assigns the current version automatically. */
    public function test_create_case_uses_current_version(): void {
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $student = core_user::get_user_by_username('student1');
        $plannings = plannings::get_plannings_for_situation_id($situation->get('id'), $student->id);
        $planning = array_shift($plannings);
        $current = case_version::get_current();
        $this->assertNotNull($current);
        $fields = [1 => 'test', 2 => 'test'];
        $caseid = cases::create_case($planning['id'], $student->id, $fields);
        $entry = case_entry::get_record(['id' => $caseid]);
        $this->assertNotNull($entry);
        $this->assertSame($current->get('id'), $entry->get('versionid'));
        $this->assertSame('validated', $entry->get('status'));
    }

    /** Test that create_case can save a draft. */
    public function test_create_case_draft_status(): void {
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $student = core_user::get_user_by_username('student1');
        $plannings = plannings::get_plannings_for_situation_id($situation->get('id'), $student->id);
        $planning = array_shift($plannings);
        $fields = [1 => 'test', 2 => 'test'];
        $caseid = cases::create_case($planning['id'], $student->id, $fields, 'draft');
        $entry = case_entry::get_record(['id' => $caseid]);
        $this->assertNotNull($entry);
        $this->assertSame('draft', $entry->get('status'));
    }

    /** Test that update_case preserves the entry's version. */
    public function test_update_case_preserves_version(): void {
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $student = core_user::get_user_by_username('student1');
        $plannings = plannings::get_plannings_for_situation_id($situation->get('id'), $student->id);
        $planning = array_shift($plannings);
        $entries = cases::get_entries($planning['id'], $student->id);
        $case = $entries->cases[0];
        $caseid = $case->id;
        $originalversion = $case->versionid;
        $datecasfield = case_field::get_record(['idnumber' => 'date_cas']);
        $newdata = [$datecasfield->get('id') => '01 March 2024'];
        $this->setAdminUser();
        cases::update_case($caseid, $newdata);
        $updated = case_entry::get_record(['id' => $caseid]);
        $this->assertSame($originalversion, $updated->get('versionid'));
    }

    /** Test that update_case can set draft status. */
    public function test_update_case_draft_status(): void {
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $student = core_user::get_user_by_username('student1');
        $plannings = plannings::get_plannings_for_situation_id($situation->get('id'), $student->id);
        $planning = array_shift($plannings);
        $entries = cases::get_entries($planning['id'], $student->id);
        $case = $entries->cases[0];
        $caseid = $case->id;
        $datecasfield = case_field::get_record(['idnumber' => 'date_cas']);
        $newdata = [$datecasfield->get('id') => '01 March 2024'];
        $this->setAdminUser();
        cases::update_case($caseid, $newdata, 'draft');
        $updated = case_entry::get_record(['id' => $caseid]);
        $this->assertSame('draft', $updated->get('status'));
    }

    /** Test that character validation rejects values exceeding the limit. */
    public function test_validate_fields_rejects_too_long(): void {
        $current = case_version::get_current();
        $this->assertNotNull($current);
        $structure = cases::get_case_structure($current->get('id'));
        // Find a field with maxlength (transmission_clinique has 1200).
        $maxlengthfield = null;
        foreach ($structure as $category) {
            foreach ($category->fields as $field) {
                $config = json_decode((string)$field->configdata, true) ?: [];
                if (!empty($config['maxlength'])) {
                    $maxlengthfield = $field->id;
                    break 2;
                }
            }
        }
        $this->assertNotNull($maxlengthfield, 'Expected at least one bounded field');
        $longvalue = str_repeat('a', 1201);
        $fields = [$maxlengthfield => $longvalue];
        $this->expectException(\moodle_exception::class);
        cases::validate_fields($fields, $structure);
    }

    /** Test that character validation accepts values within the limit. */
    public function test_validate_fields_accepts_within_limit(): void {
        $current = case_version::get_current();
        $this->assertNotNull($current);
        $structure = cases::get_case_structure($current->get('id'));
        $maxlengthfield = null;
        foreach ($structure as $category) {
            foreach ($category->fields as $field) {
                $config = json_decode((string)$field->configdata, true) ?: [];
                if (!empty($config['maxlength'])) {
                    $maxlengthfield = $field->id;
                    break 2;
                }
            }
        }
        $this->assertNotNull($maxlengthfield, 'Expected at least one bounded field');
        $validvalue = str_repeat('a', 1200);
        $fields = [$maxlengthfield => $validvalue];
        // Should not throw.
        cases::validate_fields($fields, $structure);
    }
}
