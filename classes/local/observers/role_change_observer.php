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
 * Group change observer
 *
 * @package   mod_competvet
 * @copyright 2025 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class role_change_observer {
    /**
     * Invalidate user situation cache when a user enrolment is created.
     *
     * @param \core\event\role_assigned $event
     */
    public static function assigned(\core\event\role_assigned $event) {
        \cache_helper::invalidate_by_definition('mod_competvet', 'usersituations', [], [$event->relateduserid]);
    }
    /**
     * Invalidate user situation cache when a user enrolment is deleted.
     *
     * @param \core\event\role_unassigned $event
     */
    public static function unassigned(\core\event\role_unassigned $event) {
        \cache_helper::invalidate_by_definition('mod_competvet', 'usersituations', [], [$event->relateduserid]);
    }
}
