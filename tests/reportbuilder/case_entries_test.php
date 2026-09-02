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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace mod_competvet\reportbuilder;

use advanced_testcase;
use mod_competvet\local\persistent\case_cat;
use mod_competvet\local\persistent\case_field;
use mod_competvet\reportbuilder\datasource\case_entries;
use mod_competvet\reportbuilder\local\entities\case_entry;

/**
 * Case entries report tests.
 *
 * @package   mod_competvet
 * @copyright 2026 CALL Learning
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(case_entry::class)]
final class case_entries_test extends advanced_testcase {
    /**
     * Duplicate field idnumbers must not produce duplicate report columns.
     */
    public function test_duplicate_field_idnumbers_are_only_added_once(): void {
        $this->resetAfterTest();

        $firstcategory = new case_cat(0, (object) [
            'name' => 'First category',
            'idnumber' => 'first',
            'description' => '',
            'sortorder' => 1,
            'versionid' => 0,
        ]);
        $firstcategory->create();
        $secondcategory = new case_cat(0, (object) [
            'name' => 'Second category',
            'idnumber' => 'second',
            'description' => '',
            'sortorder' => 2,
            'versionid' => 0,
        ]);
        $secondcategory->create();

        foreach ([$firstcategory, $secondcategory] as $category) {
            $field = new case_field(0, (object) [
                'idnumber' => 'same_field',
                'name' => 'Same field',
                'type' => 'text',
                'description' => '',
                'sortorder' => 1,
                'categoryid' => $category->get('id'),
                'configdata' => null,
            ]);
            $field->create();
        }

        $entity = new case_entry();
        $entity->initialise();
        $columns = $entity->get_columns();
        $this->assertCount(1, array_filter(
            array_keys($columns),
            fn(string $identifier): bool => $identifier === 'field_same_field'
        ));

        $additionalcolumns = case_entries::get_additional_columns_from_case_def();
        $this->assertCount(1, array_filter(
            $additionalcolumns,
            static fn(string $column): bool => $column === 'case_entry:field_same_field'
        ));
    }
}
