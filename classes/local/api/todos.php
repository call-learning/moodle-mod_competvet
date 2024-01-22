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

use mod_competvet\local\persistent\todo;

/**
 * Observation  API
 *
 *
 * This is a set of API used both locally by mod_competvet and local_competvet
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class todos {

    /**
     * Ask for observation : add a todo list item for the observer
     *
     * @param string $context
     * @param int $planningid
     * @param int $observerid
     * @return int
     */
    public static function ask_for_observation(string $context, int $planningid, int $observerid, int $studentid): int {
        // First check that the same user has not yet asked for an observation.
        $existingtodos = todo::get_records([
            'userid' => $observerid,
            'type' => todo::TYPE_EVAL_OBSERVATION_ASKED,
            'status' => todo::STATUS_PENDING,
        ]);
        foreach ($existingtodos as $existingtodo) {
            $existingdata = json_decode($existingtodo->get('data'));
            if ($existingdata->planningid == $planningid && $existingdata->studentid == $studentid) {
                return $existingtodo->get('id');
            }
        }
        $todo = new todo(0, (object) [
            'userid' => $observerid,
            'status' => todo::STATUS_PENDING,
            'type' => todo::TYPE_EVAL_OBSERVATION_ASKED,
            'data' => json_encode((object) [
                'planningid' => $planningid,
                'studentid' => $studentid,
                'context' => $context,
            ]),
        ]);
        $todo->create();
        return $todo->get('id');
    }
}
