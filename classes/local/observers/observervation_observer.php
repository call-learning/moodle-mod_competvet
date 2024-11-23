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

namespace mod_competvet\local\observers;
use mod_competvet\event\observation_completed;
use mod_competvet\event\observation_requested;
use mod_competvet\local\api\todos;
use mod_competvet\local\persistent\todo;

/**
 * Monitor event related to observations
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observervation_observer {
    /**
     * An observation has been requested by a student. We need to create a todo or update it.
     * @param observation_requested $event
     * @return void
     */
    public static function observation_requested(observation_requested $event) {
        $eventdata = $event->get_data();
        ['context' => $context, 'planningid' => $planningid, 'observerid' => $observerid, 'studentid' => $studentid] =
            $eventdata['other'];
        todos::ask_for_observation($context, $planningid, $observerid, $studentid);
    }

    /**
     * An observation has been completed. We need to update the todo.
     * @param observation_completed $event
     * @return void
     */
    public static function observation_completed(observation_completed $event) {
        $eventdata = $event->get_data();
        todos::complete_todo_on_observation_completed($eventdata['objectid']);
    }
}
