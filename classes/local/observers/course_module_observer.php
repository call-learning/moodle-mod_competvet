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
/**
 * Course module observers
 *
 * @package   mod_competvet
 * @copyright 2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_module_observer {
    /**
     * Make sure to invalidate the cache when a module is created.
     *
     * @param \core\event\course_module_created $event
     * @return void
     */
    public static function module_created(\core\event\course_module_created $event) {
        $evendata = $event->get_data();
        self::reset_user_cache_from_event($evendata);
    }

    /**
     * Get module information from create or delete event
     *
     * @param array $eventdata
     * @param int|null $courseid
     * @return void
     */
    private static function reset_user_cache_from_event(array $eventdata) {
        ['courseid' => $courseid, 'other' => $otherdata] = $eventdata;
        if (!$otherdata || $otherdata['modulename'] != 'competvet') {
            return;
        }
        $instanceid = $otherdata['instanceid'];
        if (!$instanceid) {
            return;
        }
        $coursecontext = \context_course::instance($courseid);
        if (!$coursecontext) {
            return;
        }
        $userids = array_map(fn($user) => $user->id, get_enrolled_users($coursecontext) ?? []);
        if (!empty($userids)) {
            // Note : invalidate by event only works when cache has been instanciated before.
            \cache_helper::invalidate_by_definition('mod_competvet', 'usersituations', [], $userids);
        }
    }

    /**
     * Make sure to invalidate the cache when a module is deleted.
     *
     * @param \core\event\course_module_deleted $event
     * @return void
     */
    public static function module_deleted(\core\event\course_module_deleted $event) {
        self::reset_user_cache_from_event($event->get_data());
    }
}
