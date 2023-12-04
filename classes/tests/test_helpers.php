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

namespace mod_competvet\tests;

/**
 * Test Helpers
 *
 * Various helper for test within mod_competvet and local_competvet modules
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class test_helpers {
    /**
     * Remove ids so we can compare the tables.
     *
     * @param array|object $record
     * @param array $elementstoremove
     * @return void
     */
    public static function remove_elements_for_assertions(&$record, array $elementstoremove): void {
        if (is_scalar($record)) {
            return;
        }
        foreach ($record as $field => &$value) {
            foreach ($elementstoremove as $element) {
                if (str_ends_with($field, $element) || $field === $element) {
                    if (is_array($record)) {
                        unset($record[$field]);
                    } else {
                        unset($record->{$field});
                    }
                }
            }
            if (is_array($value)) {
                foreach ($value as &$subrecord) {
                    self::remove_elements_for_assertions($subrecord, $elementstoremove);
                }
            }
            self::remove_elements_for_assertions($value, $elementstoremove);
        }
    }
}
