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

/**
 * Import Caselog form schema from JSON.
 *
 * @package    mod_competvet
 * @copyright  2026 CALL Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use mod_competvet\local\importer\caselog_schema_importer;

// CLI options.
[$options, $unrecognized] = cli_get_params(
    ['help' => false, 'file' => $CFG->dirroot . '/mod/competvet/data/caselog_form_schema.json', 'verbose' => false],
    ['h' => 'help', 'f' => 'file', 'v' => 'verbose']
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized), 2);
}

if ($options['help']) {
    $help = <<<EOF
Import Caselog form schema from JSON.

Options:
-h, --help          Print out this help
-f, --file          Path to the JSON schema file (default: data/caselog_form_schema.json)
-v, --verbose       Show detailed output

Example:
\$ sudo -u www-data /usr/bin/php admin/cli/import_caselog_schema.php -f /path/to/schema.json

EOF;

    echo $help;
    exit(0);
}

// Validate file path.
$realpath = realpath($options['file']);
if ($realpath === false || !is_file($realpath)) {
    cli_error("Schema file not found or not readable: {$options['file']}");
}
if (pathinfo($realpath, PATHINFO_EXTENSION) !== 'json') {
    cli_error("Schema file must be a JSON file: {$options['file']}");
}

try {
    $log = caselog_schema_importer::import($options['file'], $options['verbose']);
    foreach ($log as $message) {
        cli_whitelist($message);
    }
} catch (\Exception $e) {
    cli_error("Import failed: " . $e->getMessage());
}
