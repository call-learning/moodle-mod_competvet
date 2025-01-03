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

declare(strict_types=1);

namespace mod_competvet\reportbuilder\local\helpers;

use mod_competvet\local\persistent\case_data;

/**
 * Class containing helper methods for formatting column data via callbacks.
 *
 * @copyright 2024 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package mod_competvet
 */
class case_entry_format {

    /**
     * Format the todo data.
     */
    public static function format_field(mixed $value, \stdClass $row, \stdClass $field): string {
        if (null === $value || empty($row->entryid)) {
            return '';
        }
        if (empty($field)) {
            return '';
        }
        $casedata = case_data::get_record(['fieldid' => $field->id, 'entryid' => $row->entryid]);
        if (empty($casedata)) {
            return '';
        }

        return $casedata->get_display_value();
    }
}
