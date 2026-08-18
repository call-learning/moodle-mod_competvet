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
use mod_competvet\setup;
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

    /**
     * Verify the published version contains the configured Caselog content.
     *
     * @return void
     */
    public function test_current_version_contains_configured_content(): void {
        $current = case_version::get_current();
        $this->assertNotNull($current);
        $metadata = $current->read_metadata();
        $this->assertSame('Ajouter une transmission de cas clinique', $metadata['tutorialtitle']);
        $this->assertStringContainsString('Synthétisez un ou plusieurs cas cliniques', $metadata['tutorial']);
        $this->assertStringContainsString('Cette section évalue votre capacité', $metadata['chapo']);

        $structure = cases::get_version_structure($current->get('id'));
        $fields = [];
        foreach ($structure as $category) {
            foreach ($category->fields as $field) {
                $fields[$field->idnumber] = $field;
            }
        }

        $expectedfields = [
            'nom_animal', 'espece', 'num_dossier', 'date_cas', 'role_charge',
            'transmission_clinique', 'reflexions_enseignements',
        ];
        foreach ($expectedfields as $idnumber) {
            $this->assertArrayHasKey($idnumber, $fields);
        }
        $this->assertArrayNotHasKey('diag_final', $fields);
        $this->assertSame('Transmission clinique (1200 caractères maximum)', $fields['transmission_clinique']->name);
        $this->assertSame(1200, $this->get_field_config($fields['transmission_clinique'])['maxlength']);
        $this->assertSame(800, $this->get_field_config($fields['reflexions_enseignements'])['maxlength']);
    }

    /**
     * Verify API payloads retain version metadata for historical entries.
     *
     * @return void
     */
    public function test_get_entry_exposes_version_metadata(): void {
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $student = core_user::get_user_by_username('student1');
        $planning = array_shift(plannings::get_plannings_for_situation_id($situation->get('id'), $student->id));
        $entry = array_shift(cases::get_entries($planning['id'], $student->id)->cases);

        $metadata = json_decode($entry->versionmetadata, true);
        $this->assertIsArray($metadata);
        $this->assertSame('Ajouter un cas clinique', $metadata['tutorialtitle']);
    }

    /**
     * Verify pre-versioned entries are migrated to the legacy version and validated.
     *
     * @return void
     */
    public function test_migrate_legacy_case_entries(): void {
        global $DB;

        $situation = situation::get_record(['shortname' => 'SIT1']);
        $student = core_user::get_user_by_username('student1');
        $planning = array_shift(plannings::get_plannings_for_situation_id($situation->get('id'), $student->id));
        $entry = array_shift(cases::get_entries($planning['id'], $student->id)->cases);
        $legacy = case_version::get_record(['name' => 'Legacy Caselog']);
        $this->assertNotNull($legacy);

        $DB->set_field('competvet_case_entry', 'versionid', 0, ['id' => $entry->id]);
        $DB->set_field('competvet_case_entry', 'status', '', ['id' => $entry->id]);

        setup::migrate_legacy_case_entries();

        $migrated = case_entry::get_record(['id' => $entry->id]);
        $this->assertSame($legacy->get('id'), $migrated->get('versionid'));
        $this->assertSame('validated', $migrated->get('status'));
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

    /**
     * Verify entries from legacy and current versions can be read together.
     *
     * @return void
     */
    public function test_get_entries_supports_mixed_versions(): void {
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $student = core_user::get_user_by_username('student1');
        $planning = array_shift(plannings::get_plannings_for_situation_id($situation->get('id'), $student->id));
        $current = case_version::get_current();
        $this->assertNotNull($current);
        $legacy = case_version::get_record(['name' => 'Legacy Caselog']);
        $this->assertNotNull($legacy);
        $animal = $this->get_current_field('nom_animal');
        $species = $this->get_current_field('espece');
        cases::create_case($planning['id'], $student->id, [
            $animal->id => 'Current case',
            $species->id => 'Chien',
        ]);

        $entries = cases::get_entries($planning['id'], $student->id)->cases;
        $versionids = array_map(static fn($entry): int => $entry->versionid, $entries);
        $this->assertContains($legacy->get('id'), $versionids);
        $this->assertContains($current->get('id'), $versionids);
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

    /**
     * Verify editing a legacy entry preserves fields absent from the current form.
     *
     * @return void
     */
    public function test_update_case_preserves_legacy_values(): void {
        $situation = situation::get_record(['shortname' => 'SIT1']);
        $student = core_user::get_user_by_username('student1');
        $planning = array_shift(plannings::get_plannings_for_situation_id($situation->get('id'), $student->id));
        $entry = array_shift(cases::get_entries($planning['id'], $student->id)->cases);
        $legacyreflection = null;
        $datefieldid = null;
        foreach ($entry->categories as $category) {
            foreach ($category->fields as $field) {
                if ($field->idnumber === 'reflexions_cas') {
                    $legacyreflection = $field->value;
                }
                if ($field->idnumber === 'date_cas') {
                    $datefieldid = $field->id;
                }
            }
        }
        $this->assertNotNull($legacyreflection);
        $this->assertNotNull($datefieldid);

        $this->setAdminUser();
        cases::update_case($entry->id, [$datefieldid => '01 April 2024']);
        $updated = cases::get_entry($entry->id);
        $updatedreflection = null;
        foreach ($updated->categories as $category) {
            foreach ($category->fields as $field) {
                if ($field->idnumber === 'reflexions_cas') {
                    $updatedreflection = $field->value;
                }
            }
        }
        $this->assertSame($legacyreflection, $updatedreflection);
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
        $transmission = $this->get_current_field('transmission_clinique');
        $fields = [$transmission->id => str_repeat('a', 1201)];
        $this->expectException(\moodle_exception::class);
        cases::validate_fields($fields, $structure);
    }

    /** Test that character validation accepts values within the limit. */
    public function test_validate_fields_accepts_within_limit(): void {
        $current = case_version::get_current();
        $this->assertNotNull($current);
        $structure = cases::get_case_structure($current->get('id'));
        $transmission = $this->get_current_field('transmission_clinique');
        $reflection = $this->get_current_field('reflexions_enseignements');
        $fields = [
            $transmission->id => str_repeat('a', 1200),
            $reflection->id => str_repeat('b', 800),
        ];
        // Should not throw.
        cases::validate_fields($fields, $structure);
    }

    /**
     * Test that character validation rejects an overlong personal reflection.
     *
     * @return void
     */
    public function test_validate_fields_rejects_overlong_reflection(): void {
        $current = case_version::get_current();
        $this->assertNotNull($current);
        $structure = cases::get_case_structure($current->get('id'));
        $reflection = $this->get_current_field('reflexions_enseignements');
        $this->expectException(\moodle_exception::class);
        cases::validate_fields([$reflection->id => str_repeat('a', 801)], $structure);
    }

    /**
     * Find a field in the current published structure.
     *
     * @param string $idnumber Field identifier.
     * @return object
     */
    private function get_current_field(string $idnumber): object {
        $current = case_version::get_current();
        $structure = cases::get_case_structure($current->get('id'));
        foreach ($structure as $category) {
            foreach ($category->fields as $field) {
                if ($field->idnumber === $idnumber) {
                    return $field;
                }
            }
        }
        $this->fail('Current Caselog field not found: ' . $idnumber);
    }

    /**
     * Decode a field configuration.
     *
     * @param object $field Caselog field.
     * @return array
     */
    private function get_field_config(object $field): array {
        return json_decode(stripslashes((string)$field->configdata), true) ?: [];
    }
}
