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

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/externallib.php");

use external_api;
use external_function_parameters;
use external_value;
use external_multiple_structure;
use mod_competvet\local\api\todos as todos_api;

/**
 * Class delete_todos
 *
 * @package    mod_competvet
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class delete_todos extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'todoids' => new external_multiple_structure(new external_value(PARAM_INT, 'Todo ID', VALUE_REQUIRED)),
        ]);
    }

    public static function execute(array $todoids): void {
        todos_api::delete_todos($todoids);
    }

    public static function execute_returns(): external_value {
        return new external_value(PARAM_BOOL, 'Success');
    }
}
