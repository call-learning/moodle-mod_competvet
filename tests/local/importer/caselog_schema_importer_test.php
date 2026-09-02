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
use mod_competvet\local\persistent\case_cat;
use mod_competvet\local\persistent\case_field;
use mod_competvet\local\persistent\case_version;
use mod_competvet\local\api\cases;

/**
 * Caselog schema importer tests.
 *
 * @package     mod_competvet
 * @copyright   2026 CALL Learning <contact@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
/**
 * Tests for case log schema importing.
 *
 * @covers \mod_competvet\local\importer\caselog_schema_importer
 */
final class caselog_schema_importer_test extends advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        global $DB;
        $this->resetAfterTest();
        // Clean up any existing data to ensure test isolation.
        $DB->delete_records('competvet_case_cat');
        $DB->delete_records('competvet_case_field');
        $DB->delete_records('competvet_case_version');
    }

    /**
     * Set up a temporary JSON schema file for testing.
     *
     * @param array $schema The schema array.
     * @return string Path to the temporary file.
     */
    private function create_temp_schema_file(array $schema): string {
        global $CFG;
        $filepath = tempnam($CFG->tempdir, 'schema_test_');
        file_put_contents($filepath, json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return $filepath;
    }

    /**
     * Test importing a valid schema creates versions, categories and fields.
     */
    public function test_import_valid_schema(): void {

        $schema = $this->get_valid_schema();
        $filepath = $this->create_temp_schema_file($schema);

        $log = caselog_schema_importer::import($filepath, true);

        // Check versions were created.
        $versions = case_version::get_records([]);
        $this->assertCount(2, $versions);

        $legacyversion = case_version::get_record(['name' => 'Legacy Caselog']);
        $this->assertNotNull($legacyversion);
        $clinicalversion = case_version::get_record(['name' => 'Clinical transmission']);
        $this->assertNotNull($clinicalversion);

        // Check categories were created.
        $categories = case_cat::get_records([]);
        $this->assertCount(2, $categories); // 1 legacy + 1 clinical.

        // Check fields were created.
        $fields = case_field::get_records([]);
        $this->assertGreaterThan(0, $fields);

        // Check log messages.
        $this->assertNotEmpty($log);
        $this->assertStringContainsString('Import completed successfully', $log[array_key_last($log)]);

        unlink($filepath);
    }

    /**
     * Test idempotent re-run does not create duplicates.
     */
    public function test_idempotent_rerun(): void {
        $this->resetAfterTest();

        $schema = $this->get_valid_schema();
        $filepath = $this->create_temp_schema_file($schema);

        // First import.
        caselog_schema_importer::import($filepath);
        $firstversioncount = case_version::count_records([]);
        $firstcatcount = case_cat::count_records([]);
        $firstfieldcount = case_field::count_records([]);

        // Second import (same file).
        caselog_schema_importer::import($filepath);

        // Counts should be identical.
        $this->assertEquals($firstversioncount, case_version::count_records([]));
        $this->assertEquals($firstcatcount, case_cat::count_records([]));
        $this->assertEquals($firstfieldcount, case_field::count_records([]));

        unlink($filepath);
    }

    /**
     * Test field configuration is updated without replacing the field record.
     */
    public function test_field_configuration_update_preserves_field(): void {
        $this->resetAfterTest();

        $schema = [
            'versions' => [['key' => 'v1', 'name' => 'Version 1']],
            'categories' => [[
                'name' => 'Test Category',
                'versionkey' => 'v1',
                'fields' => [[
                    'idnumber' => 'f1',
                    'name' => 'Field 1',
                    'type' => 'text',
                    'configdata' => '{"removed":false}',
                ]],
            ]],
        ];
        $filepath = $this->create_temp_schema_file($schema);
        caselog_schema_importer::import($filepath);
        $category = case_cat::get_record(['name' => 'Test Category']);
        $field = case_field::get_record(['idnumber' => 'f1', 'categoryid' => $category->get('id')]);
        $fieldid = $field->get('id');

        $schema['categories'][0]['fields'][0]['configdata'] = '{"removed":true}';
        $schema['categories'][0]['fields'][0]['name'] = 'Updated Field';
        file_put_contents($filepath, json_encode($schema));
        caselog_schema_importer::import($filepath);

        $updatedfield = case_field::get_record(['idnumber' => 'f1', 'categoryid' => $category->get('id')]);
        $this->assertSame($fieldid, $updatedfield->get('id'));
        $this->assertSame('Updated Field', $updatedfield->get('name'));
        $this->assertSame('{"removed":true}', $updatedfield->get('configdata'));
        $version = case_version::get_record(['name' => 'Version 1']);
        $this->assertCount(0, cases::get_case_structure($version->get('id'))[0]->fields);

        unlink($filepath);
    }

    /**
     * Test invalid JSON throws an exception.
     */
    public function test_invalid_json(): void {
        $this->resetAfterTest();

        global $CFG;
        $filepath = tempnam($CFG->tempdir, 'schema_test_');
        file_put_contents($filepath, '{invalid json}');

        $this->expectException(\moodle_exception::class);
        caselog_schema_importer::import($filepath);

        unlink($filepath);
    }

    /**
     * Test missing top-level keys throws an exception.
     */
    public function test_missing_top_level_keys(): void {
        $this->resetAfterTest();

        global $CFG;
        $filepath = tempnam($CFG->tempdir, 'schema_test_');
        file_put_contents($filepath, json_encode(['onlyversions' => []]));

        $this->expectException(\moodle_exception::class);
        caselog_schema_importer::import($filepath);

        unlink($filepath);
    }

    /**
     * Test version without required keys throws an exception.
     */
    public function test_version_missing_key(): void {
        $this->resetAfterTest();

        $schema = [
            'versions' => [
                ['name' => 'Test Version'], // Missing 'key'.
            ],
            'categories' => [],
        ];
        $filepath = $this->create_temp_schema_file($schema);

        $this->expectException(\moodle_exception::class);
        caselog_schema_importer::import($filepath);

        unlink($filepath);
    }

    /**
     * Test duplicate version keys throws an exception.
     */
    public function test_duplicate_version_keys(): void {
        $this->resetAfterTest();

        $schema = [
            'versions' => [
                ['key' => 'dup', 'name' => 'Version 1'],
                ['key' => 'dup', 'name' => 'Version 2'],
            ],
            'categories' => [],
        ];
        $filepath = $this->create_temp_schema_file($schema);

        $this->expectException(\moodle_exception::class);
        caselog_schema_importer::import($filepath);

        unlink($filepath);
    }

    /**
     * Test category without required keys throws an exception.
     */
    public function test_category_missing_key(): void {
        $this->resetAfterTest();

        $schema = [
            'versions' => [['key' => 'v1', 'name' => 'Version 1']],
            'categories' => [
                ['name' => 'No Version Key'], // Missing 'versionkey'.
            ],
        ];
        $filepath = $this->create_temp_schema_file($schema);

        $this->expectException(\moodle_exception::class);
        caselog_schema_importer::import($filepath);

        unlink($filepath);
    }

    /**
     * Test field without required keys throws an exception.
     */
    public function test_field_missing_key(): void {
        $this->resetAfterTest();

        $schema = [
            'versions' => [['key' => 'v1', 'name' => 'Version 1']],
            'categories' => [
                [
                    'name' => 'Test Category',
                    'versionkey' => 'v1',
                    'fields' => [
                        ['idnumber' => 'f1', 'name' => 'Field 1'], // Missing 'type'.
                    ],
                ],
            ],
        ];
        $filepath = $this->create_temp_schema_file($schema);

        $this->expectException(\moodle_exception::class);
        caselog_schema_importer::import($filepath);

        unlink($filepath);
    }

    /**
     * Test an invalid field type aborts the import.
     */
    public function test_invalid_field_type_aborts_import(): void {
        $this->resetAfterTest();

        $schema = [
            'versions' => [['key' => 'v1', 'name' => 'Version 1']],
            'categories' => [
                [
                    'name' => 'Test Category',
                    'versionkey' => 'v1',
                    'fields' => [
                        ['idnumber' => 'valid', 'name' => 'Valid', 'type' => 'text'],
                        ['idnumber' => 'invalid', 'name' => 'Invalid Type', 'type' => 'checkbox'],
                    ],
                ],
            ],
        ];
        $filepath = $this->create_temp_schema_file($schema);

        try {
            caselog_schema_importer::import($filepath, true);
            $this->fail('An invalid field type should abort the import.');
        } catch (\moodle_exception $exception) {
            $this->assertStringContainsString('Invalid field type', $exception->getMessage());
        }

        // The transaction must roll back the valid field as well.
        $this->assertEquals(0, case_version::count_records());
        $this->assertEquals(0, case_cat::count_records());
        $this->assertEquals(0, case_field::count_records());

        unlink($filepath);
    }

    /**
     * Test legacy version is identified by name, not by key.
     *
     * A JSON with key 'legacy' but a different name should NOT trigger
     * the legacy migration path.
     */
    public function test_legacy_version_identified_by_name_not_key(): void {
        $this->resetAfterTest();

        // Create a schema where a non-legacy version has key 'legacy'.
        $schema = [
            'versions' => [
                ['key' => 'legacy', 'name' => 'Not The Legacy Version'],
                ['key' => 'v2', 'name' => 'Version 2'],
            ],
            'categories' => [
                [
                    'name' => 'Test Category',
                    'versionkey' => 'legacy',
                    'fields' => [
                        ['idnumber' => 'f1', 'name' => 'Field 1', 'type' => 'text'],
                    ],
                ],
            ],
        ];
        $filepath = $this->create_temp_schema_file($schema);

        // Import should succeed and create a new category (not try to migrate a pre-versioned one).
        $log = caselog_schema_importer::import($filepath, true);

        // The category should be created normally (no "Reused" message).
        $foundreused = false;
        foreach ($log as $msg) {
            if (strpos($msg, 'Reused pre-versioned category') !== false) {
                $foundreused = true;
                break;
            }
        }
        $this->assertFalse($foundreused, 'Legacy migration should not trigger when name does not match.');

        // The category should exist under the version with key 'legacy'.
        $legacyversion = case_version::get_record(['name' => 'Not The Legacy Version']);
        $this->assertNotNull($legacyversion);
        $cat = case_cat::get_record(['name' => 'Test Category', 'versionid' => $legacyversion->get('id')]);
        $this->assertNotNull($cat);

        // The field should have been created (not skipped).
        $field = case_field::get_record(['idnumber' => 'f1', 'categoryid' => $cat->get('id')]);
        $this->assertNotNull($field);

        unlink($filepath);
    }

    /**
     * Test non-existent version key in category is skipped gracefully.
     */
    public function test_nonexistent_version_key_skipped(): void {
        $this->resetAfterTest();

        $schema = [
            'versions' => [['key' => 'v1', 'name' => 'Version 1']],
            'categories' => [
                [
                    'name' => 'Orphan Category',
                    'versionkey' => 'nonexistent',
                    'fields' => [
                        ['idnumber' => 'f1', 'name' => 'Field 1', 'type' => 'text'],
                    ],
                ],
            ],
        ];
        $filepath = $this->create_temp_schema_file($schema);

        // Should not throw, just skip the category.
        $log = caselog_schema_importer::import($filepath, true);

        // No categories or fields should be created.
        $this->assertEquals(0, case_cat::count_records([]));
        $this->assertEquals(0, case_field::count_records([]));

        // Warning should be in the log.
        $foundwarning = false;
        foreach ($log as $msg) {
            if (strpos($msg, 'version key') !== false && strpos($msg, 'not found') !== false) {
                $foundwarning = true;
                break;
            }
        }
        $this->assertTrue($foundwarning, 'Expected a warning about missing version key.');

        unlink($filepath);
    }

    /**
     * Test upgrade scenario: pre-versioned data exists (versionid=0).
     *
     * Simulates the flow of ensure_case_versions() during an upgrade:
     * 1. Legacy categories/fields exist at versionid=0
     * 2. Importer creates the legacy version record
     * 3. Pre-versioned categories are migrated to the legacy version
     * 4. Old field IDs are preserved (no duplicates)
     * 5. New versioned categories/fields are created for other versions
     */
    public function test_upgrade_scenario_preserves_legacy_id(): void {
        $this->resetAfterTest();

        // Step 1: Simulate pre-versioned data (versionid=0) from an older version.
        $legacyversion = new case_version(0, (object) [
            'name' => 'Legacy Caselog',
            'metadata' => null,
        ]);
        $legacyversion->create();
        $originallegacyid = $legacyversion->get('id');

        $clinicalversion = new case_version(0, (object) [
            'name' => 'Clinical transmission',
            'metadata' => null,
        ]);
        $clinicalversion->create();

        // Create pre-versioned categories at versionid=0.
        $animalcat = new case_cat(0, (object) [
            'name' => 'Animal',
            'versionid' => 0,
            'idnumber' => 'c1',
            'sortorder' => 1,
            'description' => '',
        ]);
        $animalcat->create();
        $originalanimalcatid = $animalcat->get('id');

        $clinicalcat = new case_cat(0, (object) [
            'name' => 'Clinical Case',
            'versionid' => 0,
            'idnumber' => 'c2',
            'sortorder' => 2,
            'description' => '',
        ]);
        $clinicalcat->create();

        // Create fields under the pre-versioned categories.
        $animalfield1 = new case_field(0, (object) [
            'idnumber' => 'name',
            'name' => 'Name',
            'type' => 'text',
            'categoryid' => $originalanimalcatid,
            'sortorder' => 1,
            'description' => '',
            'configdata' => null,
        ]);
        $animalfield1->create();

        $animalfield2 = new case_field(0, (object) [
            'idnumber' => 'age',
            'name' => 'Age',
            'type' => 'text',
            'categoryid' => $originalanimalcatid,
            'sortorder' => 2,
            'description' => '',
            'configdata' => null,
        ]);
        $animalfield2->create();

        $clinicalfield = new case_field(0, (object) [
            'idnumber' => 'date',
            'name' => 'Date',
            'type' => 'date',
            'categoryid' => $clinicalcat->get('id'),
            'sortorder' => 1,
            'description' => '',
            'configdata' => null,
        ]);
        $clinicalfield->create();

        // Step 2: Import the new schema.
        $schema = $this->get_valid_schema();
        $filepath = $this->create_temp_schema_file($schema);
        $log = caselog_schema_importer::import($filepath, true);

        // Step 3: Verify legacy version ID is stable.
        $newlegacyversion = case_version::get_record(['name' => 'Legacy Caselog']);
        $this->assertNotNull($newlegacyversion);
        $this->assertEquals(
            $originallegacyid,
            $newlegacyversion->get('id'),
            'Legacy version ID should remain stable across upgrade.'
        );

        // Step 4: Verify pre-versioned "Animal" category was migrated to the legacy version.
        $migratedanimalcat = case_cat::get_record(['name' => 'Animal', 'versionid' => $originallegacyid]);
        $this->assertNotNull($migratedanimalcat, 'Animal category should be migrated to legacy version.');
        $this->assertEquals(
            $originalanimalcatid,
            $migratedanimalcat->get('id'),
            'Migrated category should keep its original ID.'
        );

        // Step 5: Verify old field IDs are preserved (no duplicates created).
        $oldfields = case_field::get_records(['categoryid' => $originalanimalcatid]);
        $this->assertCount(2, $oldfields, 'Original fields should be preserved under migrated category.');

        // Step 6: Verify "Clinical Case" was created fresh for clinical-transmission version.
        $clinicalversion = case_version::get_record(['name' => 'Clinical transmission']);
        $this->assertNotNull($clinicalversion);
        $newclinicalcat = case_cat::get_record(['name' => 'Clinical Case', 'versionid' => $clinicalversion->get('id')]);
        $this->assertNotNull($newclinicalcat, 'Clinical Case should be created for clinical-transmission version.');
        $this->assertNotEquals(
            $originalanimalcatid,
            $newclinicalcat->get('id'),
            'New clinical category should have a different ID than the migrated one.'
        );

        // Step 7: Verify category counts.
        // After import: 1 migrated (Animal → legacy) + 1 new (Clinical Case → clinical-transmission)
        // + 1 still at versionid=0 (old Clinical Case not matching schema versionkey).
        $totalcats = case_cat::count_records([]);
        $this->assertEquals(
            3,
            $totalcats,
            'Should have 3 categories: 1 migrated, 1 new, 1 still at versionid=0 (handled by ensure_case_versions).'
        );

        // The remaining versionid=0 category would be migrated by ensure_case_versions().
        $remainingpreversioned = case_cat::count_records(['versionid' => 0]);
        $this->assertEquals(
            1,
            $remainingpreversioned,
            'One category should still be at versionid=0 (old Clinical Case).'
        );

        // Step 8: Verify the "Reused" message is in the log for the migrated category.
        $foundreused = false;
        foreach ($log as $msg) {
            if (strpos($msg, 'Reused pre-versioned category') !== false) {
                $foundreused = true;
                break;
            }
        }
        $this->assertTrue($foundreused, 'Expected "Reused pre-versioned category" message in the log.');

        unlink($filepath);
    }

    /**
     * Test file not found throws an exception.
     */
    public function test_file_not_found(): void {
        $this->resetAfterTest();

        $this->expectException(\moodle_exception::class);
        caselog_schema_importer::import('/nonexistent/path/schema.json');
    }

    /**
     * Get a valid schema for testing.
     *
     * @return array
     */
    private function get_valid_schema(): array {
        return [
            'versions' => [
                [
                    'key' => 'legacy',
                    'name' => 'Legacy Caselog',
                    'metadata' => ['tutorialtitle' => 'Ajouter un cas clinique'],
                ],
                [
                    'key' => 'clinical-transmission',
                    'name' => 'Clinical transmission',
                    'metadata' => ['tutorialtitle' => 'Ajouter une transmission'],
                ],
            ],
            'categories' => [
                [
                    'name' => 'Animal',
                    'versionkey' => 'legacy',
                    'fields' => [
                        ['idnumber' => 'name', 'name' => 'Name', 'type' => 'text', 'sortorder' => 1],
                        ['idnumber' => 'age', 'name' => 'Age', 'type' => 'text', 'sortorder' => 2],
                    ],
                ],
                [
                    'name' => 'Clinical Case',
                    'versionkey' => 'clinical-transmission',
                    'fields' => [
                        ['idnumber' => 'date', 'name' => 'Date', 'type' => 'date', 'sortorder' => 1],
                        ['idnumber' => 'notes', 'name' => 'Notes', 'type' => 'textarea', 'sortorder' => 2],
                        ['idnumber' => 'status', 'name' => 'Status', 'type' => 'select', 'sortorder' => 3],
                    ],
                ],
            ],
        ];
    }
}
