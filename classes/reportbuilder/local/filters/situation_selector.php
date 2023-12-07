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
use mod_competvet\local\persistent\situation;

/**
 * Situation selector
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class situation_selector extends base_entity_selector {
    /**
     * Get all situations
     * @return array[]
     */
    protected function get_values(): array {
        $situations = situation::get_records();
        $situationsid = array_map(function ($situation) {
            return $situation->get('id');
        }, $situations);
        $situationsnames = array_map(function ($situation) {
            return $situation->get('shortname');
        }, $situations);
        return [$situationsid, $situationsnames];
    }
}
