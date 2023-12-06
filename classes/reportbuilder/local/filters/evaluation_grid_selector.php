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

use core_reportbuilder\local\filters\base;
use core_reportbuilder\local\helpers\database;
use mod_competvet\local\persistent\evaluation_grid;
use mod_competvet\local\persistent\situation;
use MoodleQuickForm;

/**
 * Evaluation Grid selector
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class evaluation_grid_selector extends base_entity_selector {
    /**
     * Get all evaluation grids
     * @return array[]
     */
    protected function get_values(): array {
        $evaluationgrids = evaluation_grid::get_records();
        $evaluationgridsid = array_map(function ($evaluationgrid) {
            return $evaluationgrid->get('id');
        }, $evaluationgrids);
        $evaluationgridsnames = array_map(function ($evaluationgrid) {
            return $evaluationgrid->get('shortname') . ' - ' . $evaluationgrid->get('name');
        }, $evaluationgrids);
        return [$evaluationgridsid, $evaluationgridsnames];
    }
}
