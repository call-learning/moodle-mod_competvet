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
 * TODO describe file remove_pending_todos
 *
 * @package    mod_competvet
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

// Get the cli options.
list($options, $unrecognised) = cli_get_params([
    'help' => false,
    'date' => '',
], [
    'h' => 'help',
    'd' => 'date',
]);

$usage = "Remove pending todos before a specified date

Usage:
    # php remove_pending_todos.php --date=YYYYMMDD

Options:
    -h --help                   Print this help.
    -d --date                   Date in format YYYYMMDD.
";

if ($options['help']) {
    cli_writeln($usage);
    die();
}

if (empty($options['date'])) {
    cli_error('Date is required. Use --date=YYYYMMDD');
}

$date = $options['date'];
if (!preg_match('/^\d{8}$/', $date)) {
    cli_error('Invalid date format. Use YYYYMMDD');
}

$timestamp = strtotime($date);
if ($timestamp === false) {
    cli_error('Invalid date.');
}

// Create the task and set the timestamp
$task = new \mod_competvet\task\clear_pending_todos();
$task->set_timestamp($timestamp);
$task->execute();

cli_writeln('Removed all pending todos before ' . date('Y-m-d', $timestamp));