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

use cache;

/**
 * Class user_group_observer
 *
 * @package    mod_competvet
 * @copyright  2025 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_group_observer {
    /**
     * Add an action to change the planningid in a user's observations, grades, todos, certifications, list entries etc.
     *
     * @param \core\event\group_member_added $event
     */
    public static function group_member_added(\core\event\group_member_added $event) {
        $eventdata = $event->get_data();
        $userid = (int) $eventdata['relateduserid'];
        // Clear the situation cache for this user.
        $situationcache = cache::make('mod_competvet', 'usersituations');
        if ($situationcache->has($userid)) {
            return $situationcache->delete($userid);
        }
    }

    /**
     * Add an action to change the planningid in a user's observations, grades, todos, certifications, list entries etc.
     *
     * @param \core\event\group_member_removed $event
     */
    public static function group_member_removed(\core\event\group_member_removed $event) {
        $eventdata = $event->get_data();
        $userid = (int) $eventdata['relateduserid'];
        // Clear the situation cache for this user.
        $situationcache = cache::make('mod_competvet', 'usersituations');
        if ($situationcache->has($userid)) {
            return $situationcache->delete($userid);
        }
    }
}
