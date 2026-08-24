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
 * Detect plannings whose referenced Moodle groups no longer exist.
 *
 * @package    mod_competvet
 * @copyright  2026 CALL Learning <contact@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use mod_competvet\competvet;
use mod_competvet\local\api\plannings;
use mod_competvet\local\persistent\group_history;
use mod_competvet\local\persistent\planning;
use mod_competvet\local\persistent\situation;

[$options] = cli_get_params([
    'help' => false,
    'situationid' => null,
    'planningid' => null,
], [
    'h' => 'help',
    's' => 'situationid',
    'p' => 'planningid',
]);

$usage = "Detect plannings with missing Moodle groups

Usage:
    php detect_missing_groups.php [options]

Options:
    -h, --help              Print this help.
    -s, --situationid=<id>  Restrict to one situation.
    -p, --planningid=<id>   Restrict to one planning.
";

if ($options['help']) {
    cli_writeln($usage);
    exit(0);
}

if ($options['situationid'] !== null && (!is_numeric($options['situationid']) || (int) $options['situationid'] <= 0)) {
    cli_error('Situation ID must be a positive integer.');
}

if ($options['planningid'] !== null && (!is_numeric($options['planningid']) || (int) $options['planningid'] <= 0)) {
    cli_error('Planning ID must be a positive integer.');
}

// CLI execution is the privileged maintenance path for all plannings.
$USER = get_admin();

$missing = plannings::detect_missing_groups(
    $options['situationid'] !== null ? (int) $options['situationid'] : null,
    $options['planningid'] !== null ? (int) $options['planningid'] : null
);

if (empty($missing)) {
    cli_writeln('No plannings with missing groups found.');
    exit(0);
}

cli_writeln("Found " . count($missing) . " planning(s) with missing Moodle group(s):\n");

$heading = sprintf(
    "%-5s %-12s %-20s %-8s %-10s %-10s %-12s %-10s\n",
    'Planning',
    'Situation',
    'Group ID',
    'Session',
    'Start',
    'End',
    'History',
    'History Name'
);
cli_writeln($heading);
cli_writeln(str_repeat('-', strlen($heading) - 1));

foreach ($missing as $row) {
    $historylabel = $row->history_present ? 'YES' : 'NO';
    $historyname = $row->history_present ? $row->history_name : '';
    cli_writeln(sprintf(
        "%-5d %-12s %-20s %-8s %-10s %-10s %-12s %-10s",
        $row->planningid,
        $row->situationname,
        $row->groupid,
        $row->session,
        date('Y-m-d', $row->startdate),
        date('Y-m-d', $row->enddate),
        $historylabel,
        $historyname
    ));
}

cli_writeln("\nUse the import command to add missing group-history metadata.");
exit(1);
