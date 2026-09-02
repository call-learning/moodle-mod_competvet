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

namespace mod_competvet\local\upgrade;

use advanced_testcase;
use mod_competvet\local\persistent\case_cat;
use mod_competvet\local\persistent\case_data;
use mod_competvet\local\persistent\case_field;
use mod_competvet\local\persistent\case_version;

/**
 * Caselog schema deduplication tests.
 *
 * @package     mod_competvet
 * @copyright   2026 CALL Learning
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\mod_competvet\local\upgrade\caselog_schema_deduplicator::class)]
final class caselog_schema_deduplicator_test extends advanced_testcase {
    /**
     * Verify duplicate categories and fields are merged into the oldest records.
     */
    public function test_duplicate_categories_and_fields_are_merged(): void {
        global $DB;
        $this->resetAfterTest();
        $DB->delete_records('competvet_case_cat');
        $DB->delete_records('competvet_case_field');
        $DB->delete_records('competvet_case_version');

        $version = new case_version(0, (object) ['name' => 'Legacy Caselog', 'metadata' => null]);
        $version->create();

        $firstcategory = new case_cat(0, (object) [
            'name' => 'Cas clinique', 'idnumber' => 'clinical-case', 'description' => '', 'sortorder' => 1,
            'versionid' => $version->get('id'),
        ]);
        $firstcategory->create();
        $firstfield = new case_field(0, (object) [
            'idnumber' => 'date_cas', 'name' => 'Date', 'type' => 'date', 'description' => '',
            'sortorder' => 1, 'categoryid' => $firstcategory->get('id'), 'configdata' => null,
        ]);
        $firstfield->create();

        $duplicatecategory = new case_cat(0, (object) [
            'name' => 'Cas clinique', 'idnumber' => 'clinical-case-duplicate', 'description' => '', 'sortorder' => 2,
            'versionid' => $version->get('id'),
        ]);
        $duplicatecategory->create();
        $duplicatefield = new case_field(0, (object) [
            'idnumber' => 'date_cas', 'name' => 'Date', 'type' => 'date', 'description' => '',
            'sortorder' => 1, 'categoryid' => $duplicatecategory->get('id'), 'configdata' => null,
        ]);
        $duplicatefield->create();
        $casevalue = new case_data(0, (object) [
            'fieldid' => $duplicatefield->get('id'), 'entryid' => 1, 'intvalue' => 1767225600,
        ]);
        $casevalue->create();

        $newversion = new case_version(0, (object) ['name' => 'Clinical transmission', 'metadata' => null]);
        $newversion->create();
        $newcategory = new case_cat(0, (object) [
            'name' => 'Cas clinique', 'idnumber' => 'clinical-case', 'description' => '', 'sortorder' => 1,
            'versionid' => $newversion->get('id'),
        ]);
        $newcategory->create();
        $newfield = new case_field(0, (object) [
            'idnumber' => 'date_cas', 'name' => 'Date de la prise en charge concernée', 'type' => 'date', 'description' => '',
            'sortorder' => 1, 'categoryid' => $newcategory->get('id'), 'configdata' => null,
        ]);
        $newfield->create();

        caselog_schema_deduplicator::execute();

        $this->assertSame(1, case_cat::count_records(['versionid' => $version->get('id')]));
        $this->assertSame(1, case_field::count_records(['categoryid' => $firstcategory->get('id')]));
        $this->assertTrue($DB->record_exists('competvet_case_field', ['id' => $firstfield->get('id')]));
        $this->assertFalse($DB->record_exists('competvet_case_field', ['id' => $duplicatefield->get('id')]));
        $this->assertTrue($DB->record_exists('competvet_case_data', [
            'id' => $casevalue->get('id'), 'fieldid' => $firstfield->get('id'),
        ]));
        $this->assertSame(1, case_cat::count_records(['versionid' => $newversion->get('id')]));
        $this->assertTrue($DB->record_exists('competvet_case_field', ['id' => $newfield->get('id')]));
    }

    /**
     * Verify schema fields misplaced in another category are reconciled.
     */
    public function test_misplaced_schema_fields_are_reconciled(): void {
        global $CFG, $DB;
        $this->resetAfterTest();
        $DB->delete_records('competvet_case_data');
        $DB->delete_records('competvet_case_fields');
        $DB->delete_records('competvet_case_cat');
        $DB->delete_records('competvet_case_field');
        $DB->delete_records('competvet_case_version');

        $legacy = new case_version(0, (object) ['name' => 'Legacy Caselog', 'metadata' => null]);
        $legacy->create();
        $clinical = new case_version(0, (object) ['name' => 'Clinical transmission', 'metadata' => null]);
        $clinical->create();

        $animal = new case_cat(0, (object) [
            'name' => "L'animal", 'idnumber' => 'animal', 'description' => '', 'sortorder' => 1,
            'versionid' => $legacy->get('id'),
        ]);
        $animal->create();
        $clinicalcase = new case_cat(0, (object) [
            'name' => 'Cas clinique', 'idnumber' => 'clinical-case', 'description' => '', 'sortorder' => 2,
            'versionid' => $legacy->get('id'),
        ]);
        $clinicalcase->create();
        $care = new case_cat(0, (object) [
            'name' => 'Prise en charge', 'idnumber' => 'care', 'description' => '', 'sortorder' => 3,
            'versionid' => $legacy->get('id'),
        ]);
        $care->create();

        $canonical = new case_field(0, (object) [
            'idnumber' => 'date_cas', 'name' => 'Date', 'type' => 'date', 'description' => '',
            'sortorder' => 7, 'categoryid' => $clinicalcase->get('id'), 'configdata' => null,
        ]);
        $canonical->create();
        $misplaced = new case_field(0, (object) [
            'idnumber' => 'date_cas', 'name' => 'Date', 'type' => 'date', 'description' => '',
            'sortorder' => 7, 'categoryid' => $animal->get('id'), 'configdata' => null,
        ]);
        $misplaced->create();
        $casevalue = new case_data(0, (object) [
            'fieldid' => $misplaced->get('id'), 'entryid' => 1, 'intvalue' => 1767225600,
        ]);
        $casevalue->create();

        caselog_schema_deduplicator::reconcile_schema_fields(
            $CFG->dirroot . '/mod/competvet/data/caselog_form_schema.json'
        );

        $this->assertFalse($DB->record_exists('competvet_case_field', ['id' => $misplaced->get('id')]));
        $this->assertTrue($DB->record_exists('competvet_case_field', [
            'id' => $canonical->get('id'), 'categoryid' => $clinicalcase->get('id'),
        ]));
        $this->assertTrue($DB->record_exists('competvet_case_data', [
            'id' => $casevalue->get('id'), 'fieldid' => $canonical->get('id'),
        ]));
    }
}
