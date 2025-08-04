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
 * Assign roles to the competvet module.
 *
 * @package    mod_competvet
 * @copyright  2025 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_competvet\utils;

require('../../config.php');

require_login();

[$cm, $course, $moduleinstance] = utils::page_requirements('view');

$modulecontext = context_module::instance($cm->id);

$url = new moodle_url('/mod/competvet/roleassign.php', []);
$PAGE->set_url($url);
$PAGE->set_context($modulecontext);

$renderer = $PAGE->get_renderer('mod_competvet');

echo $OUTPUT->header();

$roleassign = new \mod_competvet\output\view\role_assign($course->id, $cm->id);
echo $renderer->render($roleassign);

echo $OUTPUT->footer();
