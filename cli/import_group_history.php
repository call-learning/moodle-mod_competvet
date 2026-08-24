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
 * Import historical group metadata for plannings.
 *
 * @package    mod_competvet
 * @copyright  2026 CALL Learning <contact@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use mod_competvet\local\api\plannings;
use mod_competvet\local\persistent\group_history;
use mod_competvet\local\persistent\planning;

[$options] = cli_get_params([
    'help' => false,
    'dryrun' => false,
    'file' => null,
    'planningid' => null,
    'groupname' => null,
], [
    'h' => 'help',
    'd' => 'dryrun',
    'f' => 'file',
    'p' => 'planningid',
    'n' => 'groupname',
]);

$usage = "Import historical group metadata for plannings

Usage:
    php import_group_history.php [options]

Options:
    -h, --help              Print this help.
    -d, --dryrun            Preview changes without writing.
    -f, --file=<path>       CSV file with planningid, groupname.
    -p, --planningid=<id>   Single planning ID (use with --groupname).
    -n, --groupname=<name>  Group name (use with --planningid).

CSV file format (header required):
    planningid,groupname

Examples:
    php import_group_history.php --dryrun --file=history.csv
    php import_group_history.php --planningid=5 --groupname=\"Old Group\"
    php import_group_history.php --file=history.csv
";

if ($options['help']) {
    cli_writeln($usage);
    exit(0);
}

// CLI execution is the privileged maintenance path.
$USER = get_admin();

$dryrun = $options['dryrun'];

// Single-row mode.
if ($options['planningid'] !== null) {
    if (empty($options['groupname'])) {
        cli_error('When specifying --planningid, you must also provide --groupname.');
    }
    $planningid = (int) $options['planningid'];
    $groupname = $options['groupname'];
    $rows = [[$planningid, $groupname]];
} else if ($options['file'] !== null) {
    // CSV file mode.
    if (!file_exists($options['file'])) {
        cli_error("File not found: {$options['file']}");
    }
    $rows = [];
    $handle = fopen($options['file'], 'r');
    if ($handle === false) {
        cli_error("Cannot open file: {$options['file']}");
    }
    $header = fgetcsv($handle);
    if ($header === false) {
        cli_error("Empty CSV file: {$options['file']}");
    }
    while (($row = fgetcsv($handle)) !== false) {
        if (count($row) < 2) {
            cli_writeln("WARNING: Skipping malformed row: " . implode(',', $row));
            continue;
        }
        $rows[] = $row;
    }
    fclose($handle);
} else {
    cli_error('Either --file or --planningid/--groupname is required.');
}

$results = plannings::import_group_history($rows, $dryrun);

$success = 0;
$errors = 0;
$duplicates = 0;

foreach ($results as $result) {
    switch ($result->status) {
        case 'created':
            cli_writeln("OK: {$result->message}");
            $success++;
            break;
        case 'dryrun':
            cli_writeln("DRY-RUN: {$result->message}");
            $success++;
            break;
        case 'duplicate':
            cli_writeln("DUPLICATE: {$result->message}");
            $duplicates++;
            break;
        case 'error':
            cli_writeln("ERROR: {$result->message}");
            $errors++;
            break;
    }
}

cli_writeln("\nSummary: {$success} processed, {$errors} errors, {$duplicates} duplicates.");
if ($dryrun) {
    cli_writeln("(Dry-run mode: no changes were made.)");
}
exit($errors > 0 ? 1 : 0);
