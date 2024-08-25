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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * CLI script to test API through CURL.
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');
debugging() || defined('BEHAT_SITE_RUNNING') || die();

global $CFG;
require_once($CFG->libdir . '/clilib.php');

// Get the cli options.
[$options, $unrecognised] = cli_get_params([
    'help' => false,
    'scenario' => null,
], [
    'c' => 'command',
    's' => 'scenario',
    'h' => 'help',
]);

$usage = "Run test scenario

Usage:
    # php run_test_scenario.php [--help|-h]

Options:
    -h --help                   Print this help.
    -s --scenario                Scenario to execute
";
if ($unrecognised) {
    $unrecognised = implode("\n\t", $unrecognised);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognised));
}

if ($options['help']) {
    cli_writeln($usage);
    die();
}
$testsscenariorunner = new \mod_competvet\tests\test_scenario();
$content = $options['scenario'] ?? 'scenario_1';
$content = file_get_contents($CFG->dirroot . '/mod/competvet/tests/behat/' . $content . '.feature');
$parsedfeature = $testsscenariorunner->parse_feature($content);
$result = $testsscenariorunner->execute($parsedfeature);
if (!$result) {
    foreach ($parsedfeature->get_scenarios() as $scenario) {
        foreach ($scenario->steps as $step) {
            cli_writeln("Step: " . $step->get_text() . $step->get_error());
        }
    }
}

cli_writeln('Done !');
