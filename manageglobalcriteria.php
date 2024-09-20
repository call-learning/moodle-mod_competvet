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
 * TODO describe file manageglobalcriteria
 *
 * @package    mod_competvet
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_competvet\output\view\base;

require('../../config.php');

require_login();

if (!is_siteadmin()) {
    throw new moodle_exception('error:permission', 'mod_competvet');
}

$url = new moodle_url('/mod/competvet/manageglobalcriteria.php', []);
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
$PAGE->set_heading($SITE->fullname);
echo $OUTPUT->header();
$settingspage = new moodle_url('/admin/settings.php', ['section' => 'mod_competvet_settings']);
$iconback = html_writer::tag('i', '', ['class' => 'icon fa fa-arrow-left']);
echo html_writer::tag(
    'p',
    html_writer::link($settingspage, $iconback . get_string('settings', 'core'), ['class' => 'btn btn-primary']),
    ['class' => 'mb-3']
);
$renderer = $PAGE->get_renderer('mod_competvet');
$widget = base::factory($USER->id, 'managecriteria');
$widget->set_data();
$url = new moodle_url('/mod/competvet/criteria.php');
echo $renderer->render($widget);
echo $OUTPUT->footer();
