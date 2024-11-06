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
// This is for 4.4 compatibility.
defined('MOODLE_INTERNAL') || die;
global $CFG;
require_once("$CFG->libdir/externallib.php");

use context_system;
use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;

/**
 * Class get_json
 * FOR DEV PURPOSES ONLY
 *
 * @package    mod_competvet
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_json extends external_api {
    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'filename' => new external_value(PARAM_TEXT, 'Filename', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Execute and return json data.
     *
     * @param string $filename - The course module id
     * @return array $data - The plannings list
     * @throws \invalid_parameter_exception
     */
    public static function execute(string $filename): array {
        global $CFG;
        $params = self::validate_parameters(self::execute_parameters(),
            [
                'filename' => $filename,
            ]
        );
        self::validate_context(context_system::instance());
        $filename = $params['filename'];

        $filelocation = $CFG->dirroot . '/mod/competvet/json/' . $filename . '.json';

        $json = file_get_contents($filelocation);
        return [
            'data' => $json,
        ];
    }

    /**
     * Returns description of method return value
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure(
            [
                'data' => new external_value(PARAM_RAW, 'Data'),
            ]
        );
    }
}
