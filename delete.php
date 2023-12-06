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
 * Prints an instance of mod_competvet.
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_competvet\utils;

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
global $DB, $PAGE, $OUTPUT;

$entityid = required_param('entityid', PARAM_INT);

[$cm, $course, $moduleinstance, $tabs, $currenttype] = utils::page_requirements('delete');

echo $OUTPUT->header();
echo $OUTPUT->tabtree($tabs, $currenttype);
$hasconfirmed = optional_param('confirm', 0, PARAM_INT);
if ($hasconfirmed) {
    switch ($currenttype) {
        case 'eval':
            $observationid = $entityid;
            $observation = new \mod_competvet\local\persistent\observation(0, (object) [
                'id' => $entityid,
            ]);
            $observation->delete();
            $observationcriteria =
                \mod_competvet\local\persistent\observation_criterion::get_records(['observationid' => $observationid]);
            foreach ($observationcriteria as $criterion) {
                $criterion->delete();
            }
            break;
    }
    redirect(new moodle_url('/mod/competvet/view.php', ['id' => $cm->id, 'currenttype' => $currenttype]));
} else {
    echo $OUTPUT->confirm(get_string('confirm'),
        new moodle_url('/mod/competvet/delete.php', [
            'id' => $cm->id,
            'currenttype' => $currenttype,
            'confirm' => 1,
            'entityid' => $entityid,
        ]), new moodle_url('/mod/competvet/view.php', ['id' => $cm->id]));
}

echo $OUTPUT->footer();
