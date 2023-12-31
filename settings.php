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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin administration pages are defined here.
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('mod_competvet_settings', new lang_string('pluginname', 'mod_competvet'));

    // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
    if ($ADMIN->fulltree) {
        // Get current year.
        $currentyear = date('Y');
        $settings->add(
            new admin_setting_configtext(
                'mod_competvet/defaultsession',
                get_string('planning:defaultsession', 'mod_competvet'),
                get_string('planning:defaultsession', 'mod_competvet'),
                $currentyear,
                PARAM_INT,
            )
        );
        // TODO: Define actual plugin settings page and add it to the tree - {@link https://docs.moodle.org/dev/Admin_settings}.
    }
}
