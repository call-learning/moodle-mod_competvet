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

declare(strict_types=1);

namespace mod_competvet\reportbuilder\local\filters;

use mod_competvet\local\persistent\grid;

/**
 * Evaluation Grid selector
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class grid_selector extends base_entity_selector {
    /**
     * Get all evaluation grids
     * @return array[]
     */
    protected function get_values(): array {
        $grids = grid::get_records();
        $gridsid = array_map(function ($grid) {
            return $grid->get('id');
        }, $grids);
        $gridsnames = array_map(function ($grid) {
            return $grid->get('name') . ' - ' . $grid->get('idnumber');
        }, $grids);
        return [$gridsid, $gridsnames];
    }
}
