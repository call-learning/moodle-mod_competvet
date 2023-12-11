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
namespace mod_competvet\local\api;

use core_reportbuilder\local\filters\date;
use mod_competvet\local\persistent\observation;
use mod_competvet\reportbuilder\local\helpers\data_retriever_helper;
use mod_competvet\reportbuilder\local\systemreports\observations_per_planning;

/**
 * Plannings API
 *
 * This is a set of API used both locally by mod_competvet and local_competvet
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observations {
    /**
     * Get all observations statistics for a given planning
     *
     * @param int $planningid
     * @param bool $isstudent
     * @return array
     */
    public static function get_stats_for_planning_id(int $planningid, bool $isstudent): array {
        $context = \context_system::instance();
        $allobservations = data_retriever_helper::get_data_from_system_report(
            observations_per_planning::class,
            $context,
            ['onlyforplanningid' => "$planningid",
                'onlyforstatus' => join(',', [
                    observation::STATUS_NOTSTARTED, observation::STATUS_INPROGRESS,
                ]),
            ]
        );
        $requiredevals = $allobservations[0]['situation:evalnum'] ?? 0;

        return ['nbtoeval' => count($allobservations), 'required' => $requiredevals];
    }
}
