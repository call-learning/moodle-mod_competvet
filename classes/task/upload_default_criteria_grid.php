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
namespace mod_competvet\task;

use core\task\adhoc_task;


/**
 * Import default grid task
 *
 * @package   local_cveteval
 * @copyright 2021 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class upload_default_criteria_grid extends adhoc_task {
    /**
     * Create the grid
     *
     * @return void
     */
    public function execute() {
        static::create_default_grid();
    }

    /**
     * Create the default grid
     * @return void
     */
    public static function create_default_grid() {
        global $CFG;
        $importhelperclass = "\\mod_competvet\\local\\importer\\evaluation_grid\\import_helper";
        $importhelper = new $importhelperclass(
            $CFG->dirroot . '/mod/competvet/data/default_evaluation_grid.csv', 0, 'semicolon');
        $importhelper->import();
    }
}
