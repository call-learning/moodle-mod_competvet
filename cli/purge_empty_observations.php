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
 * Purge empty CompetVet observations.
 *
 * @package    mod_competvet
 * @copyright 2026 CALL Learning <contact@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use mod_competvet\local\api\observations;
use mod_competvet\local\persistent\planning;

[$options] = cli_get_params([
    'help' => false,
    'planningid' => null,
], [
    'h' => 'help',
    'p' => 'planningid',
]);

$usage = "Purge empty CompetVet observations

Usage:
    php purge_empty_observations.php [--planningid=<id>]

Options:
    -h, --help                 Print this help.
    -p, --planningid=<id>      Restrict the purge to one planning.
";

if ($options['help']) {
    cli_writeln($usage);
    exit(0);
}

if ($options['planningid'] !== null && (!is_numeric($options['planningid']) || (int) $options['planningid'] <= 0)) {
    cli_error('Planning ID must be a positive integer.');
}

// CLI execution is the privileged maintenance path for all plannings.
$USER = get_admin();
$planningids = $options['planningid'] === null
    ? array_keys(planning::get_records())
    : [(int) $options['planningid']];
$deleted = 0;
foreach ($planningids as $planningid) {
    $deleted += observations::purge_empty_observations($planningid);
}
cli_writeln("Purged {$deleted} empty observation(s).");
